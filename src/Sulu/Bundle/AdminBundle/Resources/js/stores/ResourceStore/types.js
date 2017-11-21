// @flow
import {observable} from 'mobx';

export type SchemaEntry = {
    label: string,
    type: string,
};

export type Schema = {
    [string]: SchemaEntry,
};

export type ObservableOptions = {
    locale?: observable,
};
