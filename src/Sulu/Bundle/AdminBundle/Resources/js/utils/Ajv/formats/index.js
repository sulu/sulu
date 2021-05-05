// @flow
import {FormatValidator} from 'ajv';
import idnEmailValidator from './idnEmailValidator';

const formats: {[string]: typeof FormatValidator} = {
    'idn-email': idnEmailValidator,
};

export default formats;
