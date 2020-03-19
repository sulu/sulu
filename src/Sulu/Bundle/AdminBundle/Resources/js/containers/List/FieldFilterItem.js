// @flow
import React from 'react';
import type {Node} from 'react';
import {action, autorun, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import ArrowMenu from '../../components/ArrowMenu';
import Button from '../../components/Button';
import Chip from '../../components/Chip';
import Loader from '../../components/Loader';
import {translate} from '../../utils/Translator';
import AbstractFieldFilterType from './fieldFilterTypes/AbstractFieldFilterType';
import listFieldFilterTypeRegistry from './registries/listFieldFilterTypeRegistry';
import fieldFilterItemStyles from './fieldFilterItem.scss';

type Props = {|
    column: string,
    filterType: ?string,
    filterTypeParameters: ?{[string]: mixed},
    label: string,
    onChange: (column: string, value: mixed) => void,
    onClick: (column: string) => void,
    onClose: () => void,
    onDelete: (column: string) => void,
    open: boolean,
    value: mixed,
|};

@observer
class FieldFilterItem extends React.Component<Props> {
    @observable value: mixed;
    fieldFilterType: AbstractFieldFilterType<*>;
    valueDisposer: () => void;
    valueNodeDisposer: () => void;
    @observable valueNodeLoading: boolean = false;
    @observable valueNode: ?Node;

    constructor(props: Props) {
        super(props);

        const {filterType, filterTypeParameters, value} = this.props;

        this.value = value;

        if (!filterType) {
            throw new Error(
                'The field does not have a "filterType". This should not happen and is likely a bug.'
            );
        }

        this.fieldFilterType = new (listFieldFilterTypeRegistry.get(filterType))(
            this.handleFieldFilterTypeChange,
            filterTypeParameters,
            value
        );

        this.valueDisposer = autorun(() => {
            this.fieldFilterType.setValue(this.value);
        });

        this.valueNodeDisposer = autorun(() => {
            const valueNodePromise = this.fieldFilterType.getValueNode(this.propValue);

            if (valueNodePromise) {
                this.setValueNodeLoading(true);
                valueNodePromise.then(action((valueNode) => {
                    this.setValueNodeLoading(false);
                    this.setValueNode(valueNode);
                }));
            }
        });
    }

    @computed get propValue() {
        return this.props.value;
    }

    @action componentDidUpdate(prevProps: Props) {
        const {open, value} = this.props;
        if (prevProps.open === false && open === true) {
            this.value = value;
        }
    }

    componentWillUnmount() {
        this.valueDisposer();
        this.valueNodeDisposer();
        this.fieldFilterType.destroy();
    }

    @action setValueNodeLoading(valueNodeLoading: boolean) {
        this.valueNodeLoading = valueNodeLoading;
    }

    @action setValueNode(valueNode: ?Node) {
        this.valueNode = valueNode;
    }

    @action handleFieldFilterTypeChange = (value: mixed) => {
        this.value = value;
    };

    handleButtonClick = () => {
        const {column, onChange} = this.props;
        onChange(column, this.value);
    };

    render() {
        const {column, label, onClick, onClose, onDelete, open} = this.props;

        return (
            <ArrowMenu
                anchorElement={
                    <span className={fieldFilterItemStyles.fieldFilterItem}>
                        <Chip
                            onClick={onClick}
                            onDelete={onDelete}
                            size="medium"
                            skin="primary"
                            value={column}
                        >
                            {label}: {this.valueNodeLoading
                                ? <Loader size={10} />
                                : this.valueNode
                            }
                        </Chip>
                    </span>
                }
                onClose={onClose}
                open={open}
            >
                <ArrowMenu.Section>
                    {this.fieldFilterType.getFormNode()}
                    <div className={fieldFilterItemStyles.buttonContainer}>
                        <Button onClick={this.handleButtonClick} skin="link">{translate('sulu_admin.ok')}</Button>
                    </div>
                </ArrowMenu.Section>
            </ArrowMenu>
        );
    }
}

export default FieldFilterItem;
