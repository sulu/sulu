// @flow
import FormInspector from '../FormInspector';

export default function(data: Object, dataPath: ?string, formInspector: FormInspector): {[string]: any} {
    return {__locale: formInspector.locale?.get()};
}
