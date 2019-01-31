// @flow
import React from 'react';
import {action, autorun, observable, toJS, when} from 'mobx';
import {observer} from 'mobx-react';
import Button from '../../components/Button';
import Overlay from '../../components/Overlay';
import ResourceListStore from '../../stores/ResourceListStore';
import {translate} from '../../utils/Translator';
import EditLine from './EditLine';
import editOverlayStyles from './editOverlay.scss';

type Props = {|
    displayProperty: string,
    idProperty: string,
    onClose: () => void,
    open: boolean,
    resourceListStore: ResourceListStore,
    title: ?string,
|};

@observer
export default class EditOverlay extends React.Component<Props> {
    @observable data: Array<Object>;
    updateDataDisposer: () => void;

    constructor(props: Props) {
        super(props);

        this.updateDataDisposer = autorun(() => this.updateData(this.props.resourceListStore.data));
    }

    @action updateData = (data: Array<Object>) => {
        this.data = toJS(data);
    };

    componentWillUnmount() {
        this.updateDataDisposer();
    }

    @action handleEditLineChange = (index: number, value: ?string) => {
        const {displayProperty} = this.props;
        this.data[index][displayProperty] = value;
    };

    @action handleEditLineRemove = (index: number) => {
        this.data.splice(index, 1);
    };

    @action handleEditLineAdd = () => {
        const {displayProperty} = this.props;
        this.data.push({[displayProperty]: undefined});
    };

    @action handleConfirm = () => {
        const {displayProperty, idProperty, onClose, resourceListStore} = this.props;

        const entriesToAdd = this.data.filter((dataEntry) => !dataEntry[idProperty]);

        const entriesToDelete = resourceListStore.data
            .filter((entry) => !this.data.some((dataEntry) => dataEntry[idProperty] === entry[idProperty]));

        const entriesToUpdate = this.data.filter((dataEntry) => {
            const entry = resourceListStore.data.find((entry) => dataEntry[idProperty] === entry[idProperty]);

            return entry && entry[displayProperty] !== dataEntry[displayProperty];
        });

        if (entriesToDelete.length > 0) {
            resourceListStore.deleteList(entriesToDelete.map((entry) => entry[idProperty]));
        }

        if (entriesToAdd.length > 0 || entriesToUpdate.length > 0) {
            resourceListStore.patchList([...entriesToAdd, ...entriesToUpdate]);
        }

        when(
            () => !resourceListStore.loading,
            onClose
        );
    };

    render() {
        const {displayProperty, onClose, open, resourceListStore, title} = this.props;

        return (
            <Overlay
                confirmLoading={resourceListStore.loading}
                confirmText={translate('sulu_admin.ok')}
                onClose={onClose}
                onConfirm={this.handleConfirm}
                open={open}
                size="small"
                title={title || translate('sulu_admin.edit_entries')}
            >
                <div className={editOverlayStyles.overlay}>
                    {this.data.map((object, index) => (
                        <EditLine
                            id={index}
                            key={index}
                            onChange={this.handleEditLineChange}
                            onRemove={this.handleEditLineRemove}
                            value={object[displayProperty]}
                        />
                    ))}
                    <Button
                        icon="su-plus"
                        onClick={this.handleEditLineAdd}
                        skin="icon"
                    />
                </div>
            </Overlay>
        );
    }
}
