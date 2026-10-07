// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import FormOverlay from 'sulu-admin-bundle/containers/FormOverlay';
import {resourceFormStoreFactory} from 'sulu-admin-bundle/containers/Form';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import type {ResourceFormStore} from 'sulu-admin-bundle/containers/Form';
import type {IObservableValue} from 'mobx/lib/mobx';

type Props = {|
    id: ?number,
    locale: IObservableValue<string>,
    onClose: () => void,
    onConfirm: () => void,
    open: boolean,
|};

const MEDIA_RESOURCE_KEY = 'media';
const MEDIA_DETAILS_FORM_KEY = 'media_details';

@observer
class MediaEditOverlay extends React.Component<Props> {
    @observable formStore: ?ResourceFormStore;

    componentDidMount() {
        const {id, open} = this.props;

        if (open && id) {
            this.createFormStore(id);
        }
    }

    componentDidUpdate(prevProps: Props) {
        const {id, open} = this.props;

        if (open && id && (!prevProps.open || prevProps.id !== id)) {
            this.createFormStore(id);
        }

        if (!open && prevProps.open) {
            this.destroyFormStore();
        }
    }

    componentWillUnmount() {
        this.destroyFormStore();
    }

    @action createFormStore(id: number) {
        const {locale} = this.props;

        this.destroyFormStore();

        const resourceStore = new ResourceStore(MEDIA_RESOURCE_KEY, id, {locale});
        this.formStore = resourceFormStoreFactory.createFromResourceStore(resourceStore, MEDIA_DETAILS_FORM_KEY);
    }

    @action destroyFormStore() {
        if (this.formStore) {
            this.formStore.destroy();
            this.formStore = undefined;
        }
    }

    render() {
        const {onClose, onConfirm, open} = this.props;
        const {formStore} = this;

        if (!formStore) {
            return null;
        }

        return (
            <FormOverlay
                confirmDisabled={!formStore.dirty}
                confirmText={translate('sulu_admin.save')}
                formStore={formStore}
                onClose={onClose}
                onConfirm={onConfirm}
                open={open}
                size="large"
                title={translate('sulu_media.edit_media')}
            />
        );
    }
}

export default MediaEditOverlay;
