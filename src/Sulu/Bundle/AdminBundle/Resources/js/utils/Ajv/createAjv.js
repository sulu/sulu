// @flow
import Ajv, {type Options} from 'ajv';
import applyAjvKeywords from 'ajv-keywords';
import applyAjvFormats from 'ajv-formats';
import type {KeywordDefinition} from 'ajv';
import customKeywords from './keywords';
import customFormats from './formats';

const createAjv = (options: Options = {allErrors: true}) => {
    const ajv = new Ajv(options);

    applyAjvKeywords(ajv);
    applyAjvFormats(ajv);

    customKeywords.forEach((keyword: KeywordDefinition) => {
        ajv.addKeyword(keyword);
    });

    Object.entries(customFormats).forEach(([name, format]) => {
        ajv.addFormat(name, format);
    });

    return ajv;
};

export default createAjv;
