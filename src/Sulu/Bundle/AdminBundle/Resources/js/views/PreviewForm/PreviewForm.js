// @flow
import jexl from 'jexl';
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
    const enablePreview = !previewCondition || jexl.evalSync(previewCondition, this.resourceFormStore.data);

    return enablePreview ? {
        view: 'sulu_preview.preview',
        sizes: ['medium', 'large'],
        props: {
            router: this.props.router,
            formStore: this.resourceFormStore,
        },
    } : null;
});
