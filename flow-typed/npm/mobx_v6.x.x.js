// flow-typed signature: local
// flow-typed version: <<STUB>>/mobx_v6.x.x

declare module 'mobx' {
    declare export type IObservableArray<T> = Array<T>;

    declare export type IValueWillChange<T> = {
        object: {
            get(): T,
            ...,
        },
        newValue: T,
        type: string,
        ...,
    };

    declare export interface IObservableValue<T> {
        get(): T;
        set(value: T): void;
        intercept(handler: (change: IValueWillChange<T>) => ?IValueWillChange<T>): () => mixed;
        observe(handler: (change: mixed) => mixed, fireImmediately?: boolean): () => mixed;
    }

    declare export var action: any;
    declare export var autorun: any;
    declare export var comparer: any;
    declare export var computed: any;
    declare export var configure: any;
    declare export var extendObservable: any;
    declare export var get: any;
    declare export var intercept: any;
    declare export var isArrayLike: any;
    declare export var isObservable: any;
    declare export var isObservableArray: any;
    declare export var makeObservable: any;
    declare export var observable: any;
    declare export var observe: any;
    declare export var reaction: any;
    declare export var set: any;
    declare export var toJS: any;
    declare export var untracked: any;
    declare export var when: any;
}
