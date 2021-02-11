// @flow
import Ajv, {type Options} from 'ajv';
import formats from './formats';

const createAjv = (options: Options = {allErrors: true, jsonPointers: true}) => {
    const ajv = new Ajv(options);

    Object.entries(formats).forEach(([name, format]) => {
        ajv.addFormat(name, format);
    });

    return ajv;
};

export default createAjv;
