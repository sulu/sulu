// @flow
import {FormatValidator} from 'ajv';
import bicValidator from './bicValidator';
import ibanValidator from './ibanValidator';
import idnEmailValidator from './idnEmailValidator';

const formats: {[string]: FormatValidator} = {
    'bic': bicValidator,
    'iban': ibanValidator,
    'idn-email': idnEmailValidator,
};

export default formats;
