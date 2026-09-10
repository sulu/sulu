// @flow
import validateEmail from '../../Email/validateEmail';

const idnEmailValidator = (data: string): boolean => {
    return validateEmail(data);
};

export default idnEmailValidator;
