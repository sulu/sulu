// @flow
import Iban from 'iban';
import {FormatValidator} from 'ajv';

const ibanValidator: FormatValidator = (data: string): boolean => {
    return Iban.isValid(data);
};

export default ibanValidator;
