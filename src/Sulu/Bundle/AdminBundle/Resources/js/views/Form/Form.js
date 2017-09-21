// @flow
import React from 'react';
import {default as FormContainer} from '../../containers/Form';
import {translate} from '../../services/Translator';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer/types';

class Form extends React.PureComponent<ViewProps> {
    form: ?FormContainer;

    handleSubmit = () => {
        console.log('Submit!');
    };

    setForm = (form) => {
        this.form = form;
    };

    render() {
        const schema = {
            title: {
                label: 'Title',
                type: 'text_line',
            },
            url: {
                label: 'URL',
                type: 'text_line',
            },
            important: {
                label: 'Important?',
                type: 'checkbox',
            },
        };

        return (
            <div>
                <FormContainer ref={this.setForm} onSubmit={this.handleSubmit} schema={schema} />
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
                onClick: () => {
                    this.form.submit();
                },
            },
        ],
    };
});
