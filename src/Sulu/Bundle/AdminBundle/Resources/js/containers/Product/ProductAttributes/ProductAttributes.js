// @flow
import React from 'react';
import {action, computed, observable, reaction, toJS} from 'mobx';
import {observer} from 'mobx-react';
import {translate} from '../../../utils/Translator';
import Input from '../../../components/Input';
import Loader from '../../../components/Loader';
import Toggler from '../../../components/Toggler';
import Router from '../../../services/Router';
import FormInspector from '../../Form/FormInspector';
import memoryFormStoreFactory from '../../Form/stores/memoryFormStoreFactory';
import ProductAttributesRenderer from './ProductAttributesRenderer';
import isEmpty from './isEmpty';
import {NAME_PREFIX} from './namePrefix';
import productAttributesRendererStyles from './productAttributesRenderer.scss';
import type {ErrorCollection, FormStoreInterface} from '../../Form/types';

const FORM_KEY = 'product_attributes';

type Props = {|
    dataPath: string,
    disabled: boolean,
    formInspector: FormInspector,
    onChange: (value: {[string]: mixed}) => void,
    onFinish: () => void,
    router: ?Router,
    showAllErrors: boolean,
    value: ?{[string]: mixed},
    variant: boolean,
|};

type Selector = {productFamily: string} | {product: string};

/**
 * Edits a product's attribute values in its own form store, built from the product_attributes form
 * of the selected family (or the parent's family for a new variant). Errors in this store block
 * the save of the host form.
 *
 * The store keys its data by field name (attribute_<id>), the host value by attribute id.
 *
 * @experimental We can not yet give BC Promise for this new container in Sulu 3.1.
 */
@observer
class ProductAttributes extends React.Component<Props> {
    @observable formStore: ?FormStoreInterface = undefined;
    @observable formInspector: ?FormInspector = undefined;
    @observable hideEmpty: boolean = false;
    @observable filter: string = '';

    selectorDisposer: () => void;
    removeFieldValidator: () => void;

    constructor(props: Props) {
        super(props);

        const {dataPath, formInspector} = props;
        this.removeFieldValidator = formInspector.addFieldValidator(dataPath, this.validate);

        this.selectorDisposer = reaction(
            () => this.selector,
            this.createFormStore,
            {equals: (a, b) => JSON.stringify(a) === JSON.stringify(b), fireImmediately: true}
        );
    }

    componentDidUpdate(prevProps: Props) {
        if (prevProps.value !== this.props.value) {
            this.syncValue();
        }
    }

    componentWillUnmount() {
        this.selectorDisposer();
        this.removeFieldValidator();
        this.destroyFormStore();
    }

    @computed get selector(): ?Selector {
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

    @computed get value(): {[string]: mixed} {
        return this.props.value || {};
    }

    // Empty values stay out of the data, so the JSON schema's required check catches them.
    get data(): {[string]: mixed} {
        const {value} = this;
        const data = {};

        Object.keys(value).forEach((id) => {
            if (!isEmpty(value[id])) {
                data[NAME_PREFIX + id] = value[id];
            }
        });

        return data;
    }

    @action createFormStore = (selector: ?Selector) => {
        this.destroyFormStore();

        if (!selector) {
            return;
        }

        const {formInspector, variant} = this.props;
        const metadataOptions = variant ? {...selector, variant: true} : {...selector};

        const formStore = memoryFormStoreFactory.createFromFormKey(
            FORM_KEY,
            this.data,
            formInspector.locale,
            undefined,
            metadataOptions
        );
        this.formStore = formStore;
        this.formInspector = new FormInspector(formStore);
    };

    @action destroyFormStore() {
        if (this.formStore) {
            this.formStore.destroy();
        }

        this.formStore = undefined;
        this.formInspector = undefined;
    }

    // A reload of the host form replaces the value; the store follows without becoming dirty.
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

    validate = (): ?ErrorCollection => {
        const {formStore} = this;
        if (!formStore || formStore.loading) {
            return undefined;
        }

        formStore.validate();

        return formStore.hasErrors ? toJS(formStore.errors) : undefined;
    };

    handleChange = (name: string, fieldValue: mixed) => {
        const {formStore} = this;
        if (!formStore) {
            throw new Error('A row changed without a form store. This should not happen and is likely a bug.');
        }

        formStore.change('/' + name, fieldValue);
        this.props.onChange({...this.value, [name.substring(NAME_PREFIX.length)]: fieldValue});
    };

    // Same steps as Form.handleFieldFinish on the inner form, then the host field finishes.
    handleFinish = (dataPath: string, schemaPath: string) => {
        const {formInspector, formStore} = this;
        if (!formStore || !formInspector) {
            throw new Error('A row finished without a form store. This should not happen and is likely a bug.');
        }

        formStore.validate();
        formInspector.finishField(dataPath, schemaPath);
        this.props.onFinish();
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
        const {disabled, router, showAllErrors} = this.props;
        const {formInspector, formStore} = this;

        if (!formStore || !formInspector) {
            return (
                <p className={productAttributesRendererStyles.hint}>
                    {translate('sulu_product.select_product_family_for_attributes')}
                </p>
            );
        }

        if (formStore.loading) {
            return <Loader />;
        }

        return (
            <ProductAttributesRenderer
                data={formStore.data}
                disabled={disabled}
                filter={this.filter}
                formInspector={formInspector}
                hideEmpty={this.hideEmpty}
                onChange={this.handleChange}
                onFinish={this.handleFinish}
                router={router}
                schema={formStore.schema}
                showAllErrors={showAllErrors}
                toolbar={this.renderToolbar()}
            />
        );
    }
}

export default ProductAttributes;
