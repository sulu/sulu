// @flow
import React from 'react';
import {default as FormContainer} from '../../containers/Form';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer';
import {translate} from '../../services/Translator';
import ResourceStore from '../../stores/ResourceStore';
import formStyles from './form.scss';

type Props = ViewProps & {
    resourceStore: ResourceStore,
};

class Form extends React.PureComponent<Props> {
    form: ?FormContainer;

    componentWillMount() {
        const {resourceStore, router} = this.props;

        if (resourceStore.locale) {
            router.bind('locale', resourceStore.locale);
        }
    }

    componentWillUnmount() {
        const {resourceStore, router} = this.props;

        if (resourceStore.locale) {
            router.unbind('locale', resourceStore.locale);
        }
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
                const {resourceStore} = this.props;

                const options = {};
                if (resourceStore.locale) {
                    options.locale = resourceStore.locale.get();
                }
                router.restore(backRoute, options);
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
