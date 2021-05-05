// @flow
import IsEmail from 'isemail';
import {FormatValidator} from 'ajv';

const idnEmailValidator: typeof FormatValidator = (data: string): boolean => {
    return IsEmail.validate(data);
};

export default idnEmailValidator;
