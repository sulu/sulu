// @flow
import React from 'react';
import {default as FormContainer} from '../../containers/Form';
import FormStore from '../../containers/Form/stores/FormStore';
import {translate} from '../../services/Translator';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer';

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
        const {router} = this.props;
        const {
            route: {
                options: {
                    resourceKey,
                },
            },
            attributes: {
                id,
            },
        } = router;
        this.formStore = new FormStore(resourceKey, id);
        this.formStore.changeSchema(schema);
        router.bindQuery('locale', this.formStore.locale);
    }

    componentWillUnmount() {
        this.formStore.destroy();
        this.props.router.unbindQuery('locale');
    }

    handleSubmit = () => {
        this.formStore.save();
    };

    setFormRef = (form) => {
        this.form = form;
    };

    render() {
        return (
            <div>
                <FormContainer
                    ref={this.setFormRef}
                    store={this.formStore}
                    onSubmit={this.handleSubmit}
                    schema={schema}
                />
            </div>
        );
    }
}

export default withToolbar(Form, function() {
    const {router} = this.props;
    const {backRoute, locales} = router.route.options;

    const backButton = backRoute
        ? {
            onClick: () => {
                router.navigate(backRoute, {}, {locale: this.formStore.locale.get()});
            },
        }
        : undefined;
    const locale = locales
        ? {
            value: this.formStore.locale.get(),
            onChange: (locale) => {
                this.formStore.setLocale(locale);
            },
            options: locales.map((locale) => ({
                value: locale,
                label: locale,
            })),
        }
        : undefined;

    return {
        backButton,
        locale,
        items: [
            {
                type: 'button',
                value: translate('sulu_admin.save'),
                icon: 'floppy-o',
                disabled: !this.formStore.dirty,
                loading: this.formStore.saving,
                onClick: () => {
                    this.form.submit();
                },
            },
        ],
    };
});
