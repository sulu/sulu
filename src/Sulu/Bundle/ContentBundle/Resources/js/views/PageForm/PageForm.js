// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {FormStore, Form, withToolbar} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import {translate} from 'sulu-admin-bundle/utils';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import WebspaceStore from '../../stores/WebspaceStore';
import type {Webspace} from '../../stores/WebspaceStore/types';
import pageFormStyles from './pageForm.scss';

type Props = ViewProps & {
    resourceStore: ResourceStore,
};

@observer
class PageForm extends React.Component<Props> {
    formStore: FormStore;
    form: ?Form;
    @observable webspace: Webspace;
    @observable errors = [];
    showSuccess = observable.box(false);

    constructor(props: Props) {
        super(props);

        const {resourceStore, router} = this.props;
        this.formStore = new FormStore(resourceStore);

        if (resourceStore.locale) {
            router.bind('locale', resourceStore.locale);
        }

        WebspaceStore.loadWebspace(router.attributes.webspace)
            .then(action((webspace) => {
                this.webspace = webspace;
            }));
    }

    componentWillUnmount() {
        this.formStore.destroy();
    }

    @action showSuccessSnackbar = () => {
        this.showSuccess.set(true);
    };

    handleSubmit = (actionParameter) => {
        const {resourceStore, router} = this.props;

        const {
            route: {
                options: {
                    editRoute,
                },
            },
        } = router;

        const saveOptions = {
            webspace: router.attributes.webspace,
            action: actionParameter,
            parent: undefined,
        };

        if (editRoute) {
            resourceStore.destroy();
            saveOptions.parent = router.attributes.parentId;
        }

        return this.formStore.save(saveOptions)
            .then((response) => {
                this.showSuccessSnackbar();
                if (editRoute) {
                    router.navigate(
                        editRoute,
                        {id: resourceStore.id, locale: resourceStore.locale, webspace: router.attributes.webspace}
                    );
                }

                return response;
            })
            .catch((errorResponse) => {
                return errorResponse.json().then(action((error) => {
                    this.errors.push(error);
                }));
            });
    };

    setFormRef = (form) => {
        this.form = form;
    };

    render() {
        return (
            <div className={pageFormStyles.form}>
                <Form
                    ref={this.setFormRef}
                    store={this.formStore}
                    onSubmit={this.handleSubmit}
                />
            </div>
        );
    }
}

export default withToolbar(PageForm, function() {
    const {router} = this.props;
    const formTypes = this.formStore.types;
    const webspace = this.webspace;

    const backButton = {
        onClick: () => {
            const {resourceStore} = this.props;

            const options = {};
            options.locale = resourceStore.locale.get();
            router.restore('sulu_content.webspaces', options);
        },
    };

    const locale = webspace
        ? {
            value: this.props.resourceStore.locale.get(),
            onChange: (locale) => {
                this.props.resourceStore.setLocale(locale);
            },
            options: webspace.allLocalizations.map((localization) => ({
                value: localization.localization,
                label: localization.name,
            })),
        }
        : undefined;

    const items = [
        {
            type: 'dropdown',
            label: translate('sulu_admin.save'),
            icon: 'su-save',
            loading: this.props.resourceStore.saving,
            options: [
                {
                    label: translate('sulu_admin.save_draft'),
                    disabled: !this.props.resourceStore.dirty,
                    onClick: () => {
                        this.form.submit('draft');
                    },
                },
                {
                    label: translate('sulu_admin.save_publish'),
                    disabled: !this.props.resourceStore.dirty,
                    onClick: () => {
                        this.form.submit('publish');
                    },
                },
            ],
        },
    ];

    if (this.formStore.typesLoading || Object.keys(formTypes).length > 0) {
        items.push({
            type: 'select',
            icon: 'fa-paint-brush',
            onChange: (value) => {
                this.formStore.changeType(value);
            },
            loading: this.formStore.typesLoading,
            value: this.formStore.type,
            options: Object.keys(formTypes).map((key) => ({
                value: formTypes[key].key,
                label: formTypes[key].title,
            })),
        });
    }

    return {
        backButton,
        errors: this.errors,
        items,
        locale,
        showSuccess: this.showSuccess,
    };
});
