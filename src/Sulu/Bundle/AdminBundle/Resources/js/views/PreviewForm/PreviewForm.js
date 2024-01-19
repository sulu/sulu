// @flow
import jexl from 'jexl';
import {toJS} from 'mobx';
import withSidebar from '../../containers/Sidebar/withSidebar';
import Form from '../Form';

export default withSidebar(Form, function() {
    const {
        router: {
            route: {
                options: {
                    previewCondition,
                },
            },
        },
    } = this.props;
    const previewData = {
        __routeAttributes: this.props.router.attributes,
        ...toJS(this.resourceFormStore.data),
    };
    const enablePreview = !previewCondition || jexl.evalSync(previewCondition, previewData);

    const {
        resourceFormStore: {
            resourceKey,
        },
    } = this;

    return enablePreview ? {
        view: 'sulu_preview.preview',
        sizes: ['medium', 'large'],
        props: {
            router: this.props.router,
            formStore: this.resourceFormStore,
            key: resourceKey,
        },
    } : null;
});
