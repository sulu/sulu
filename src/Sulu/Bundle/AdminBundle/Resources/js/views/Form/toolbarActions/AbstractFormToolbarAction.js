// @flow
import {computed, toJS} from 'mobx';
import ResourceStore from '../../../stores/ResourceStore';
import {ResourceFormStore, FormInspector, conditionDataProviderRegistry} from '../../../containers/Form';
import Router from '../../../services/Router';
import Form from '../Form';
import type {ToolbarItemConfig} from '../../../containers/Toolbar/types';
import type {Node} from 'react';

/**
 * Split out of the class so DropdownToolbarAction can apply the same rule to its children. The cast
 * is needed because ToolbarItemConfig is a union of exact object types, which a spread cannot match.
 */
export function applyToolbarItemLock(config: ?ToolbarItemConfig<*>, locked: boolean): ?ToolbarItemConfig<*> {
    if (!config || !locked) {
        return config;
    }

    return (({...config, disabled: true}: any): ToolbarItemConfig<*>);
}

export default class AbstractFormToolbarAction {
    resourceFormStore: ResourceFormStore;
    formInspector: FormInspector;
    form: Form;
    router: Router;
    locales: ?Array<string>;
    options: {[key: string]: mixed};
    parentResourceStore: ResourceStore;

    @computed get conditionData() {
        const data = this.resourceFormStore.data;
        const formInspector = this.formInspector;

        return conditionDataProviderRegistry.getAll().reduce(
            function(data, conditionDataProvider) {
                return {...data, ...conditionDataProvider(data, undefined, formInspector)};
            },
            {...toJS(data)}
        );
    }

    constructor(
        resourceFormStore: ResourceFormStore,
        form: Form,
        router: Router,
        locales: ?Array<string>,
        options: {[key: string]: mixed},
        parentResourceStore: ResourceStore
    ) {
        this.resourceFormStore = resourceFormStore;
        this.formInspector = new FormInspector(this.resourceFormStore);
        this.form = form;
        this.router = router;
        this.locales = locales;
        this.options = options;
        this.parentResourceStore = parentResourceStore;
    }

    setLocales(locales: Array<string>) {
        this.locales = locales;
    }

    // eslint-disable-next-line no-unused-vars
    getNode(index: ?number): Node {
        return null;
    }

    getToolbarItemConfig(): ?ToolbarItemConfig<*> {
        throw new Error('The getToolbarItemConfig method must be implemented by the sub class!');
    }

    /** A locked form disables every action by default, so only actions resolving the lock opt back in. */
    get enabledWhileLocked(): boolean {
        return false;
    }

    /** Everything rendering toolbar items must use this instead of getToolbarItemConfig(). */
    getLockAwareToolbarItemConfig(): ?ToolbarItemConfig<*> {
        return applyToolbarItemLock(
            this.getToolbarItemConfig(),
            !!this.resourceFormStore.locked && !this.enabledWhileLocked
        );
    }

    destroy() {

    }
}
