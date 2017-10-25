// @flow
import React from 'react';
import {default as FormContainer} from '../../containers/Form';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer';
import {translate} from '../../services/Translator';
import ResourceStore from '../../stores/ResourceStore';
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

type Props = ViewProps & {
    resourceStore: ResourceStore,
};

class Form extends React.PureComponent<Props> {
    form: ?FormContainer;

    componentWillMount() {
        const {router} = this.props;
        this.props.resourceStore.changeSchema(schema);
        router.bindQuery('locale', this.props.resourceStore.locale);
    }

    componentWillUnmount() {
        this.props.router.unbindQuery('locale', this.props.resourceStore.locale);
    }

    handleSubmit = () => {
        this.props.resourceStore.save();
    };

    setFormRef = (form) => {
        this.form = form;
    };

    render() {
        return (
            <div className={formStyles.form}>
                <FormContainer
                    ref={this.setFormRef}
                    store={this.props.resourceStore}
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
                router.navigate(backRoute, {}, {locale: this.props.resourceStore.locale.get()});
            },
        }
        : undefined;
    const locale = locales
        ? {
            value: this.props.resourceStore.locale.get(),
            onChange: (locale) => {
                this.props.resourceStore.setLocale(locale);
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
                disabled: !this.props.resourceStore.dirty,
                loading: this.props.resourceStore.saving,
                onClick: () => {
                    this.form.submit();
                },
            },
        ],
    };
});
