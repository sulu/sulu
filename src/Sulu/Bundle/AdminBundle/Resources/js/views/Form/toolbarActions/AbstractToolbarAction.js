// @flow
import type {Node} from 'react';
import Form, {FormStore} from '../../../containers/Form';
import type {ToolbarAction, ToolbarItem} from '../../../containers/Toolbar/types';
import Router from '../../../services/Router';

export default class AbstractFormToolbarAction implements ToolbarAction {
    formStore: FormStore;
    form: Form;
    router: Router;

    constructor(formStore: FormStore, form: Form, router: Router) {
        this.formStore = formStore;
        this.form = form;
        this.router = router;
    }

    getElement(): Node {
        return null;
    }

    getToolbarItemConfig(): ToolbarItem {
        throw new Error('The getToolbarItemConfig method must be implemented by the sub class!');
    }
}
