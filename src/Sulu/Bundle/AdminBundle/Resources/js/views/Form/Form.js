// @flow
import React from 'react';
import {default as FormContainer} from '../../containers/Form';
import FormStore from '../../containers/Form/stores/FormStore';
import {translate} from '../../services/Translator';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer/types';

const schema = {
    title: {
        label: 'Title',
        type: 'text_line',
    },
    slogan: {
        label: 'Slogan',
        type: 'text_line',
    },
};

class Form extends React.PureComponent<ViewProps> {
    form: ?FormContainer;
    formStore: FormStore;

    componentWillMount() {
        this.formStore = new FormStore();
        this.formStore.changeSchema(schema);
    }

    handleSubmit = () => {
        console.log(this.formStore.data);
    };

    setForm = (form) => {
        this.form = form;
    };

    render() {
        return (
            <div>
                <FormContainer ref={this.setForm} store={this.formStore} onSubmit={this.handleSubmit} schema={schema} />
            </div>
        );
    }
}

export default withToolbar(Form, function() {
    const {router} = this.props;
    const {backRoute} = router.route.options;

    const backButton = backRoute
        ? {
            onClick: () => {
                router.navigate(backRoute);
            },
        }
        : undefined;

    return {
        backButton,
        items: [
            {
                type: 'button',
                value: translate('sulu_admin.save'),
                icon: 'floppy-o',
                disabled: !this.formStore.dirty,
                onClick: () => {
                    this.form.submit();
                },
            },
        ],
    };
});
