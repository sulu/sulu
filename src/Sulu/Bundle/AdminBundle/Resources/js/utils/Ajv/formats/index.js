// @flow
import {FormatValidator} from 'ajv';
import ibanValidator from './ibanValidator';
import idnEmailValidator from './idnEmailValidator';

const formats: {[string]: FormatValidator} = {
    'iban': ibanValidator,
    'idn-email': idnEmailValidator,
};

export default formats;
