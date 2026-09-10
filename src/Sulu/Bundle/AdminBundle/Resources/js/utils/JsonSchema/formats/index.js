// @flow
import idnEmailValidator from './idnEmailValidator';

const formats: {[string]: (data: string) => boolean} = {
    'idn-email': idnEmailValidator,
};

export default formats;
