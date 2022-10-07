// @flow
import React from 'react';
import {action, observable} from 'mobx';
import jexl from 'jexl';
import log from 'loglevel';
import Checkbox from '../../../components/Checkbox';
import Dialog from '../../../components/Dialog';
import ResourceRequester from '../../../services/ResourceRequester';
import {translate} from '../../../utils/Translator';
import {ResourceFormStore} from '../../../containers/Form';
import Router from '../../../services/Router';
import ResourceStore from '../../../stores/ResourceStore';
import Form from '../Form';
import copyLocaleActionStyles from './copyLocaleAction.scss';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class CopyLocaleToolbarAction extends AbstractFormToolbarAction {
    @observable showCopyLocaleDialog = false;
    @observable selectedLocales: Array<string> = [];
    @observable copying: boolean = false;

    constructor(
        resourceFormStore: ResourceFormStore,
        form: Form,
        router: Router,
        locales: ?Array<string>,
        options: {[key: string]: mixed},
        parentResourceStore: ResourceStore
    ) {
        const {
            display_condition: displayCondition,
            visible_condition: visibleCondition,
        } = options;

        if (displayCondition) {
            // @deprecated
            log.warn(
                'The "display_condition" option is deprecated since version 2.0 and will be removed. ' +
                'Use the "visible_condition" option instead.'
            );

            if (!visibleCondition) {
                options.visible_condition = displayCondition;
            }
        }

        super(resourceFormStore, form, router, locales, options, parentResourceStore);
    }

    getNode() {
        const {
            resourceFormStore: {
                data: {
                    availableLocales,
                },
                id,
                locale: currentLocale,
            },
            locales,
        } = this;

        if (!id) {
            return null;
        }

        if (!locales || !currentLocale) {
            throw new Error('The CopyLocaleToolbarAction for pages only works with locales!');
        }

        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmLoading={this.copying}
                confirmText={translate('sulu_admin.ok')}
                key="sulu_admin.copy_locale"
                onCancel={this.handleClose}
                onConfirm={this.handleConfirm}
                open={this.showCopyLocaleDialog}
                title={translate('sulu_admin.copy_locale')}
            >
                <div className={copyLocaleActionStyles.dialog}>
                    <p>{translate('sulu_admin.choose_target_locale')}:</p>
                    {locales.map((locale) => currentLocale.get() === locale
                        ? null
                        : <Checkbox
                            checked={this.selectedLocales.includes(locale)}
                            key={locale}
                            onChange={this.handleCheckboxChange}
                            value={locale}
                        >
                            {locale}{availableLocales && !availableLocales.includes(locale) && '*'}
                        </Checkbox>
                    )}
                    <p>{translate('sulu_admin.copy_locale_dialog_description')}</p>
                </div>
            </Dialog>
        );
    }

    getToolbarItemConfig() {
        const {
            visible_condition: visibleCondition,
        } = this.options;

        const {id} = this.resourceFormStore;

        const visibleConditionFulfilled = !visibleCondition || jexl.evalSync(visibleCondition, this.conditionData);

        if (visibleConditionFulfilled) {
            return {
                disabled: !id,
                icon: 'su-copy',
                label: translate('sulu_admin.copy_locale'),
                onClick: action(() => {
                    this.showCopyLocaleDialog = true;
                }),
                type: 'button',
            };
        }
    }

    @action handleConfirm = () => {
        this.copying = true;
        const {
            resourceFormStore: {
                id,
                locale,
                options: {
                    webspace,
                },
                resourceKey,
            },
        } = this;

        ResourceRequester.post(
            resourceKey,
            undefined,
            {
                id,
                locale,
                dest: this.selectedLocales,
                action: 'copy-locale',
                webspace,
            }
        ).then(action(() => {
            this.copying = false;
            this.showCopyLocaleDialog = false;
            this.form.showSuccessSnackbar();
            this.clearSelectedLocales();
        }));
    };

    @action handleClose = () => {
        this.showCopyLocaleDialog = false;
        this.clearSelectedLocales();
    };

    @action handleCheckboxChange = (checked: boolean, value?: string | number) => {
        if (checked && typeof value === 'string' && !this.selectedLocales.includes(value)) {
            this.selectedLocales.push(value);
        } else {
            this.selectedLocales.splice(this.selectedLocales.findIndex((locale) => locale === value), 1);
        }
    };

    @action clearSelectedLocales = () => {
        this.selectedLocales.splice(0, this.selectedLocales.length);
    };
}
