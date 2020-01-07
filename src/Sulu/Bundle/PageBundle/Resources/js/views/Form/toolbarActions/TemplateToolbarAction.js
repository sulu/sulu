// @flow
import {action, computed, observable} from 'mobx';
import {AbstractFormToolbarAction} from 'sulu-admin-bundle/views';
import type {ToolbarItemConfig} from 'sulu-admin-bundle/types';
import webspaceStore from '../../../stores/webspaceStore';
import type {Webspace} from '../../../stores/webspaceStore/types';
import {ResourceRequester} from 'sulu-admin-bundle/services';

export default class TemplateToolbarAction extends AbstractFormToolbarAction {
    @observable webspace: ?Webspace = undefined;

    @computed get defaultTemplate(): ?string {
        if (!this.webspace) {
            webspaceStore.loadWebspace(this.router.attributes.webspace).then(action((webspace) => {
                this.webspace = webspace;
            }));
            return undefined;
        }

        return this.webspace.defaultTemplates.page;
    }

    getToolbarItemConfig(): ToolbarItemConfig {
        const formTypes = this.resourceFormStore.types;
        const formKeys = Object.keys(formTypes);
        if (formKeys.length > 0 && !this.resourceFormStore.type && this.defaultTemplate) {
            this.resourceFormStore.setType(this.defaultTemplate);
        }

        if (this.router.attributes.parentId && this.webspace) {
            ResourceRequester.get('pages', {
                id: this.router.attributes.parentId,
                language: this.router.attributes.locale,
            }).then((response) => {
                const parentTemplate = response.template;
                for (const key in this.webspace.defaultTemplates) {
                    const defaultTemplate = this.webspace.defaultTemplates[key];
                    if (defaultTemplate.parentTemplate === parentTemplate) {
                        this.resourceFormStore.setType(key);
                        break;
                    }
                }
            });
        }

        if (!this.resourceFormStore.typesLoading && Object.keys(formTypes).length === 0) {
            throw new Error('The ToolbarAction for types only works with entities actually supporting types!');
        }

        return {
            type: 'select',
            icon: 'su-brush',
            onChange: (value: string | number) => {
                if (typeof value !== 'string') {
                    throw new Error('Only strings are valid as a form type!');
                }

                this.resourceFormStore.changeType(value);
            },
            loading: this.resourceFormStore.typesLoading,
            value: this.resourceFormStore.type,
            options: Object.keys(formTypes).map((key: string) => ({
                value: formTypes[key].key,
                label: formTypes[key].title,
            })),
        };
    }
}
