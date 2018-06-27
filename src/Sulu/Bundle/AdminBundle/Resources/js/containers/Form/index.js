// @flow
import Form from './Form';
import FormInspector from './FormInspector';
import fieldRegistry from './registries/FieldRegistry';
import FormStore from './stores/FormStore';
import Selection from './fields/Selection';
import SingleSelection from './fields/SingleSelection';
import Checkbox from './fields/Checkbox';
import ColorPicker from './fields/ColorPicker';
import DatePicker from './fields/DatePicker';
import Email from './fields/Email';
import Input from './fields/Input';
import Number from './fields/Number';
import PasswordConfirmation from './fields/PasswordConfirmation';
import Phone from './fields/Phone';
import SingleSelect from './fields/SingleSelect';
import ResourceLocator from './fields/ResourceLocator';
import Renderer from './Renderer';
import TextArea from './fields/TextArea';
import TextEditor from './fields/TextEditor';
import Time from './fields/Time';
import type {Schema, Types} from './types';

export {
    fieldRegistry,
    Selection,
    Checkbox,
    ColorPicker,
    DatePicker,
    Email,
    Input,
    FormInspector,
    FormStore,
    Number,
    PasswordConfirmation,
    Phone,
    Renderer,
    ResourceLocator,
    SingleSelect,
    SingleSelection,
    TextArea,
    TextEditor,
    Time,
};
export type {Schema, Types};
export default Form;
