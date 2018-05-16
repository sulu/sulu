// @flow
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import FormStore from './stores/FormStore';

export default class FormInspector {
    formStore: FormStore;

    constructor(formStore: FormStore) {
        this.formStore = formStore;
    }

    get resourceKey(): string {
        return this.formStore.resourceKey;
    }

    get locale(): ?IObservableValue<string> {
        return this.formStore.locale;
    }

    get id(): ?string | number {
        return this.formStore.id;
    }

    getValueByName(name: string): mixed {
        if (!this.formStore.data[name]) {
            throw new Error('Property with name "' + name + '" not found');
        }

        return this.formStore.data[name];
    }
}
