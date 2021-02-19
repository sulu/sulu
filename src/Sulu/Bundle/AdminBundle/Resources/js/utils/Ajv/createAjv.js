// @flow
import Ajv, {type Options} from 'ajv';
import applyAjvKeywords from 'ajv-keywords';
import applyAjvFormats from 'ajv-formats';
import customFormats from './formats';

const createAjv = (options: Options = {allErrors: true, allowUnionTypes: true}) => {
    const ajv = new Ajv(options);

    applyAjvKeywords(ajv);
    applyAjvFormats(ajv);

    Object.entries(customFormats).forEach(([name, format]) => {
        ajv.addFormat(name, format);
    });

    return ajv;
};

export default createAjv;
