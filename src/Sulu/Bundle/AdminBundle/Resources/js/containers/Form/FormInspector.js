// @flow
import {computed} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import type {FinishFieldHandler} from './types';
import FormStore from './stores/FormStore';

export default class FormInspector {
    formStore: FormStore;

    finishFieldHandlers: Array<FinishFieldHandler> = [];

    constructor(formStore: FormStore) {
        this.formStore = formStore;
    }

    @computed get resourceKey(): string {
        return this.formStore.resourceKey;
    }

    @computed get locale(): ?IObservableValue<string> {
        return this.formStore.locale;
    }

    @computed get options(): Object {
        return this.formStore.options;
    }

    @computed get id(): ?string | number {
        return this.formStore.id;
    }

    getValueByPath(path: string): mixed {
        return this.formStore.getValueByPath(path);
    }

    getValuesByTag(tagName: string): Array<mixed> {
        return this.formStore.getValuesByTag(tagName);
    }

    addFinishFieldHandler(finishFieldHandler: FinishFieldHandler) {
        this.finishFieldHandlers.push(finishFieldHandler);
    }

    finishField(schemaPath: string) {
        this.finishFieldHandlers.forEach((finishFieldHandler) => finishFieldHandler(schemaPath));
    }
}
