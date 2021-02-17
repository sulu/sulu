// @flow
import {FormatValidator} from 'ajv';

// eslint-disable-next-line max-len
// Copied from https://github.com/jquery-validation/jquery-validation/blob/bda9a58ec006e9ab866263c9209147ff6e3352ed/src/additional/bic.js#L17
const bicValidator: FormatValidator = (data: string): boolean => {
    return /^([A-Z]{6}[A-Z2-9][A-NP-Z1-9])(X{3}|[A-WY-Z0-9][A-Z0-9]{2})?$/.test(data.toUpperCase());
};

export default bicValidator;
