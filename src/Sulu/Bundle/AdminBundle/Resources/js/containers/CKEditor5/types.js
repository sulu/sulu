//@flow
import type {TextEditorConfig} from '../TextEditor/types';

export type AttributeMap = {
    [string]: string,
};

export type Config = (options: Object, textEditorConfig: TextEditorConfig) => Object;
