// @flow
import React from 'react';
import {default as FormContainer} from '../../containers/Form';
import ResourceStore from '../../stores/ResourceStore';
import {translate} from '../../services/Translator';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer';
import formStyles from './form.scss';

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

class Form extends React.Component<ViewProps> {
    form: ?FormContainer;
    resourceStore: ResourceStore;

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
        this.resourceStore = new ResourceStore(resourceKey, id);
        this.resourceStore.changeSchema(schema);
        router.bindQuery('locale', this.resourceStore.locale);
    }

    componentWillUnmount() {
        this.resourceStore.destroy();
        this.props.router.unbindQuery('locale', this.resourceStore.locale);
    }

    handleSubmit = () => {
        this.resourceStore.save();
    };

    setFormRef = (form) => {
        this.form = form;
    };

    render() {
        return (
            <div className={formStyles.form}>
                <FormContainer
                    ref={this.setFormRef}
                    store={this.resourceStore}
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
                router.navigate(backRoute, {}, {locale: this.resourceStore.locale.get()});
            },
        }
        : undefined;
    const locale = locales
        ? {
            value: this.resourceStore.locale.get(),
            onChange: (locale) => {
                this.resourceStore.setLocale(locale);
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
                disabled: !this.resourceStore.dirty,
                loading: this.resourceStore.saving,
                onClick: () => {
                    this.form.submit();
                },
            },
        ],
    };
});
