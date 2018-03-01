// @flow
import Form from './Form';
import fieldRegistry from './registries/FieldRegistry';
import FormStore from './stores/FormStore';
import Assignment from './fields/Assignment';
import Input from './fields/Input';
import SingleSelect from './fields/SingleSelect';
import ResourceLocator from './fields/ResourceLocator';
import Renderer from './Renderer';
import type {Schema, Types} from './types';

export {fieldRegistry, Assignment, Input, FormStore, Renderer, ResourceLocator, SingleSelect};
export type {Schema, Types};
export default Form;
