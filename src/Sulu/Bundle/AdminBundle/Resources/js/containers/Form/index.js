// @flow
import Form from './Form';
import FormInspector from './FormInspector';
import fieldRegistry from './registries/FieldRegistry';
import FormStore from './stores/FormStore';
import MemoryFormStore from './stores/MemoryFormStore';
import ChangelogLine from './fields/ChangelogLine';
import CardCollection from './fields/CardCollection';
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
import Select from './fields/Select';
import ResourceLocator from './fields/ResourceLocator';
import Renderer from './Renderer';
import SmartContent from './fields/SmartContent';
import TextArea from './fields/TextArea';
import TextEditor from './fields/TextEditor';
import Url from './fields/Url';
import type {Schema, Types} from './types';

export {
    fieldRegistry,
    Selection,
    CardCollection,
    Checkbox,
    ColorPicker,
    ChangelogLine,
    DatePicker,
    Email,
    Input,
    FormInspector,
    FormStore,
    MemoryFormStore,
    Number,
    PasswordConfirmation,
    Phone,
    Renderer,
    ResourceLocator,
    Select,
    SmartContent,
    SingleSelect,
    SingleSelection,
    TextArea,
    TextEditor,
    Url,
};
export type {Schema, Types};
export default Form;
