// @flow
import {Validator, format} from '@cfworker/json-schema';
import customFormats from './formats';

// Register Sulu's custom formats on the shared cfworker format registry.
Object.keys(customFormats).forEach((name) => {
    format[name] = customFormats[name];
});

// JSON Schema applicator keywords. For a failing value the cfworker validator
// emits one error per applicator (e.g. "properties", "items") in addition to
// the underlying assertion error. Ajv, which this replaces, only reported the
// leaf assertion errors and the form stores rely on that, so we drop them.
const APPLICATOR_KEYWORDS = new Set([
    'properties',
    'patternProperties',
    'additionalProperties',
    'propertyNames',
    'dependentSchemas',
    'dependencies',
    'items',
    'additionalItems',
    'prefixItems',
    'contains',
    'if',
    'then',
    'else',
    'not',
    'allOf',
    'anyOf',
    'oneOf',
    '$ref',
    '$recursiveRef',
    '$dynamicRef',
]);

const REQUIRED_PROPERTY_REGEX = /required property "(.+)"/;
const NUMBER_REGEX = /-?\d+(?:\.\d+)?/g;

// Keywords for which Ajv exposed a "limit" parameter. cfworker only renders the
// limit into the error message, always as its last number (e.g. "String is too
// short (2 < 3).", "Array has too few items (1 < 2)."), so we read it back.
const LIMIT_KEYWORDS = new Set([
    'minLength',
    'maxLength',
    'minItems',
    'maxItems',
    'minProperties',
    'maxProperties',
    'minimum',
    'maximum',
    'exclusiveMinimum',
    'exclusiveMaximum',
]);

type CfworkerError = {error: string, instanceLocation: string, keyword: string};
type ValidationError = {instancePath: string, keyword: string, params: Object};

// cfworker does not expose error parameters as structured data the way Ajv did,
// they are only available within the rendered message. We restore the ones the
// admin actually relies on ("missingProperty" to build the error path and the
// "limit" of length/range constraints); the remaining parameters are unused by
// the form rendering, which only reads the "keyword".
const extractParams = (error: CfworkerError): Object => {
    if (error.keyword === 'required') {
        const match = REQUIRED_PROPERTY_REGEX.exec(error.error);

        return match ? {missingProperty: match[1]} : {};
    }

    if (LIMIT_KEYWORDS.has(error.keyword)) {
        const numbers = error.error.match(NUMBER_REGEX);

        return numbers ? {limit: Number(numbers[numbers.length - 1])} : {};
    }

    return {};
};

// Maps a cfworker error to the Ajv error shape consumed by AbstractFormStore.
const mapError = (error: CfworkerError): ValidationError => {
    return {
        instancePath: error.instanceLocation.replace(/^#/, ''),
        keyword: error.keyword,
        params: extractParams(error),
    };
};

// The cfworker validator throws when it encounters an `undefined` instance,
// while Ajv silently treated it as an absent value. Form data regularly holds
// `undefined` for empty fields, so we normalize it the same way a JSON payload
// would: object properties set to `undefined` are dropped (an absent property
// can still trigger a "required" error, matching the previous Ajv behavior).
const normalizeData = (data: mixed): mixed => {
    const json = JSON.stringify(data ?? null);

    return json === undefined ? null : JSON.parse(json);
};

const createValidator = () => {
    return {
        compile(schema: Object) {
            const validator = new Validator(schema, '7', false);

            const validate = (data: mixed): boolean => {
                const {valid, errors} = validator.validate(normalizeData(data));

                validate.errors = valid
                    ? null
                    : errors
                        .filter((error) => !APPLICATOR_KEYWORDS.has(error.keyword))
                        .map(mapError);

                return valid;
            };

            validate.errors = null;

            return validate;
        },
    };
};

export default createValidator;
