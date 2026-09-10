// @flow
import React from 'react';
import {action, computed, observable, reaction, toJS} from 'mobx';
import {observer} from 'mobx-react';
import equals from 'fast-deep-equal';
import {translate} from '../../../utils/Translator';
import Input from '../../../components/Input';
import Loader from '../../../components/Loader';
import Toggler from '../../../components/Toggler';
import Router from '../../../services/Router';
import FormInspector from '../../Form/FormInspector';
import memoryFormStoreFactory from '../../Form/stores/memoryFormStoreFactory';
import ProductAttributesRenderer from './ProductAttributesRenderer';
import productAttributesRendererStyles from './productAttributesRenderer.scss';
import type {Row} from './ProductAttributesRenderer';
import type {Error as FieldError, ErrorCollection, FormStoreInterface} from '../../Form/types';

const FORM_KEY = 'product_attributes';

// Field names in the product_attributes form are "attribute_<id>", mirrored by the bundle's AttributeFieldFactory.
const NAME_PREFIX = 'attribute_';

function isEmpty(value: mixed): boolean {
    if (value === undefined || value === null || value === '') {
        return true;
    }

    if (Array.isArray(value)) {
        return value.length === 0;
    }

    if (typeof value === 'object') {
        return Object.keys(value).length === 0;
    }

    return false;
}

type Props = {|
    dataPath: string,
    disabled: boolean,
    error?: ?FieldError | ErrorCollection,
    formInspector: FormInspector,
    onChange: (value: {[string]: mixed}) => void,
    onFinish: (dataPath: string, schemaPath: string) => void,
    router: ?Router,
    schemaPath: string,
    showAllErrors: boolean,
    value: ?{[string]: mixed},
    variant: boolean,
|};

type Selector = {productFamily: string} | {product: string};

/**
 * Edits a product's attribute values in its own form store, built from the product_attributes form
 * of the selected family (or the parent's family for a new variant). The host form validates the
 * values through its JSON schema, the errors come back through the field's error prop.
 *
 * The store keys its data by field name (attribute_<id>), the host value by attribute id.
 *
 * @experimental We can not yet give BC Promise for this new container in Sulu 3.1.
 */
@observer
class ProductAttributes extends React.Component<Props> {
    @observable formStore: ?FormStoreInterface = undefined;
    @observable hideEmpty: boolean = false;
    @observable filter: string = '';

    selectorDisposer: () => void;

    componentDidMount() {
        // The family is read from the host store, not from a prop, so only a reaction sees it change.
        this.selectorDisposer = reaction(() => this.selector, this.createFormStore, {fireImmediately: true});
    }

    componentDidUpdate(prevProps: Props) {
        if (!equals(toJS(prevProps.value), toJS(this.props.value))) {
            this.syncValue();
        }
    }

    componentWillUnmount() {
        this.selectorDisposer();

        if (this.formStore) {
            this.formStore.destroy();
        }
    }

    @computed get formInspector(): ?FormInspector {
        const {formStore} = this;

        return formStore ? new FormInspector(formStore) : undefined;
    }

    @computed.struct get selector(): ?Selector {
        const {formInspector} = this.props;

        const productFamily = formInspector.getValueByPath('/productFamily');
        if (typeof productFamily === 'string' && productFamily) {
            return {productFamily};
        }

        const product = formInspector.options ? formInspector.options.parentId : undefined;
        if (typeof product === 'string' && product) {
            return {product};
        }

        return undefined;
    }

    get data(): {[string]: mixed} {
        const value = this.props.value || {};
        const data = {};

        Object.keys(value).forEach((id) => {
            data[NAME_PREFIX + id] = value[id];
        });

        return data;
    }

    @action createFormStore = (selector: Selector) => {
        if (this.formStore) {
            this.formStore.destroy();
        }

        const {formInspector, variant} = this.props;
        const metadataOptions = variant ? {...selector, variant: true} : {...selector};

        this.formStore = memoryFormStoreFactory.createFromFormKey(
            FORM_KEY,
            this.data,
            formInspector.locale,
            undefined,
            metadataOptions
        );
    };

    // Mirrors a host value replaced from outside, e.g. a save response or "delete draft".
    // Written as a server value so the inner store does not go dirty from a change the user did not make.
    syncValue() {
        const {formStore} = this;
        if (!formStore) {
            return;
        }

        const {data} = this;
        const changes = {};
        new Set([...Object.keys(data), ...Object.keys(formStore.data)]).forEach((name) => {
            if (formStore.data[name] !== data[name]) {
                changes['/' + name] = data[name];
            }
        });

        if (Object.keys(changes).length > 0) {
            formStore.changeMultiple(changes, {isServerValue: true});
        }
    }

    /*
     * The host errors sit at /attributes/<id>; a row shows its error once touched or when the form asks for all.
     * The numeric ids make the host store build an observable array, read as a plain one like FieldBlocks does.
     */
    @computed get errors(): {[string]: FieldError} {
        const {dataPath, error, formInspector, showAllErrors} = this.props;
        const errors = {};

        if (!error || typeof error !== 'object' || typeof error.keyword === 'string') {
            return errors;
        }

        // $FlowFixMe: an object or an array, both read by their set keys
        const rowErrors: {[string]: ?FieldError} = toJS(error);
        Object.keys(rowErrors).forEach((id) => {
            const rowError = rowErrors[id];
            if (rowError && (showAllErrors || formInspector.isFieldModified(dataPath + '/' + id))) {
                errors[NAME_PREFIX + id] = rowError;
            }
        });

        return errors;
    }

    handleChange = (name: string, fieldValue: mixed) => {
        const {formStore} = this;
        if (!formStore) {
            throw new Error('A row changed without a form store. This should not happen and is likely a bug.');
        }

        formStore.change('/' + name, fieldValue);
        this.props.onChange({...this.props.value, [name.substring(NAME_PREFIX.length)]: fieldValue});
    };

    // The row finishes on the host form under its host path, so the host validates and marks it modified.
    handleFinish = (rowDataPath: string) => {
        const {dataPath, onFinish, schemaPath} = this.props;
        const id = rowDataPath.substring(1 + NAME_PREFIX.length);

        onFinish(dataPath + '/' + id, schemaPath);
    };

    // The renderer asks per row, so the toolbar's filter and its hide-empty toggle are answered in one place.
    filterItem = (row: Row): boolean => {
        const {formStore} = this;
        const needle = this.filter.trim().toLowerCase();

        if (needle && !(row.schema.label || '').toLowerCase().includes(needle)) {
            return false;
        }

        if (this.hideEmpty && (!formStore || isEmpty(toJS(formStore.data[row.name])))) {
            return false;
        }

        return true;
    };

    @action handleHideEmptyChange = (checked: boolean) => {
        this.hideEmpty = checked;
    };

    @action handleFilterChange = (filter: ?string) => {
        this.filter = filter || '';
    };

    @action handleFilterClear = () => {
        this.filter = '';
    };

    renderToolbar() {
        return (
            <div className={productAttributesRendererStyles.toolbar}>
                <div className={productAttributesRendererStyles.search}>
                    <Input
                        icon="su-search"
                        onChange={this.handleFilterChange}
                        onClearClick={this.handleFilterClear}
                        placeholder={translate('sulu_product.filter_attributes')}
                        value={this.filter}
                    />
                </div>
                <div className={productAttributesRendererStyles.hideEmpty}>
                    <Toggler checked={this.hideEmpty} onChange={this.handleHideEmptyChange}>
                        {translate('sulu_product.hide_empty_attributes')}
                    </Toggler>
                </div>
            </div>
        );
    }

    render() {
        const {disabled, router} = this.props;
        const {formInspector, formStore} = this;

        // The store is built in componentDidMount, so the first render has none yet.
        if (!formStore || !formInspector || formStore.loading) {
            return <Loader />;
        }

        return (
            <ProductAttributesRenderer
                data={formStore.data}
                disabled={disabled}
                errors={this.errors}
                filterItem={this.filterItem}
                formInspector={formInspector}
                onChange={this.handleChange}
                onFinish={this.handleFinish}
                router={router}
                schema={formStore.schema}
                toolbar={this.renderToolbar()}
            />
        );
    }
}

export default ProductAttributes;
