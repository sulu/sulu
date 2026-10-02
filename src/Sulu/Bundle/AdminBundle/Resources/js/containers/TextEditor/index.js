// @flow
import TextEditor from './TextEditor';
import textEditorConfigRegistry from './registries/textEditorConfigRegistry';
import textEditorRegistry from './registries/textEditorRegistry';
import type {TextEditorAdapterProps, TextEditorConfig, TextEditorProps} from './types';

export {textEditorConfigRegistry, textEditorRegistry};
export type {TextEditorAdapterProps, TextEditorConfig, TextEditorProps};
export default TextEditor;
