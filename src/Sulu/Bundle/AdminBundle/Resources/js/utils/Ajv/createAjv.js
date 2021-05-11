// @flow
import Ajv, {Options} from 'ajv';
import applyAjvFormats from 'ajv-formats';
import customFormats from './formats';

const createAjv = (options: typeof Options = {allErrors: true, allowUnionTypes: true}) => {
    const ajv = new Ajv(options);

    applyAjvFormats(ajv);

    Object.entries(customFormats).forEach(([name, format]) => {
        ajv.addFormat(name, format);
    });

    return ajv;
};

export default createAjv;
