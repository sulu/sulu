// @flow
import FormInspector from '../FormInspector';

export default function(data: Object, dataPath: ?string, formInspector: FormInspector): {[string]: any} {
    // the block settings overlay passes the block owning the settings form via the form store options
    const block = formInspector.options?.__block;

    return block !== undefined ? {__block: block} : {};
}
