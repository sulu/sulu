// @flow
import IsEmail from 'isemail';
import {FormatValidator} from 'ajv';

const idnEmailValidator: FormatValidator = (data: string): boolean => {
    return IsEmail.validate(data);
};

export default idnEmailValidator;
