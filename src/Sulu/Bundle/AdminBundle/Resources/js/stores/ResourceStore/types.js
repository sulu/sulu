// @flow
import {observable} from 'mobx';
import type {Size} from '../../components/Grid';

export type SchemaEntry = {
    label: string,
    type: string,
    size?: Size,
    spaceAfter?: Size,
};

export type Schema = {
    [string]: SchemaEntry,
};

export type ObservableOptions = {
    locale?: observable,
};
