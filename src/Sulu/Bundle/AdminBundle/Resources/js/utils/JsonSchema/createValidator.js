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
// "not" is not part of the list: when it fails, its subschema has matched, so
// there is no underlying error and the "not" error itself is the leaf.
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

type CfworkerError = {error: string, instanceLocation: string, keyword: string, keywordLocation: string};
type ValidationError = {instancePath: string, keyword: string, params: Object, schema?: mixed};

// cfworker does not expose error parameters as structured data the way Ajv did,
// they are only available within the rendered message. We restore the ones the
// admin actually relies on ("missingProperty" to build the error path and the
// "limit" of length/range constraints, with the "comparison" of range ones); the remaining parameters are unused by
// the form rendering, which only reads the "keyword".
// The comparison Ajv reported along with the limit of range constraints.
const COMPARISONS = {
    minimum: '>=',
    maximum: '<=',
    exclusiveMinimum: '>',
    exclusiveMaximum: '<',
};

const extractParams = (error: CfworkerError): Object => {
    if (error.keyword === 'required') {
        const match = REQUIRED_PROPERTY_REGEX.exec(error.error);

        return match ? {missingProperty: match[1]} : {};
    }

    if (LIMIT_KEYWORDS.has(error.keyword)) {
        const numbers = error.error.match(NUMBER_REGEX);

        if (!numbers) {
            return {};
        }

        const limit = Number(numbers[numbers.length - 1]);

        return error.keyword in COMPARISONS ? {comparison: COMPARISONS[error.keyword], limit} : {limit};
    }

    return {};
};

const unescapePointerSegment = (segment: string): string => {
    return decodeURIComponent(segment).replace(/~1/g, '/').replace(/~0/g, '~');
};

// Returns the value of the failing keyword in the schema, like Ajv did with its
// "verbose" option. cfworker gives the keyword location as a JSON pointer into
// the root schema, in which a local "$ref" is a segment of its own.
const resolveKeywordValue = (rootSchema: Object, keywordLocation: string): mixed => {
    let value = rootSchema;

    for (const segment of keywordLocation.replace(/^#\/?/, '').split('/').filter(Boolean)) {
        if (value === null || typeof value !== 'object') {
            return undefined;
        }

        value = value[unescapePointerSegment(segment)];

        if (segment === '$ref') {
            if (typeof value !== 'string' || !value.startsWith('#')) {
                return undefined;
            }

            value = resolveKeywordValue(rootSchema, value);
        }
    }

    return value;
};

// Maps a cfworker error to the Ajv error shape consumed by AbstractFormStore.
const mapError = (error: CfworkerError, rootSchema: Object): ValidationError => {
    const mappedError: ValidationError = {
        instancePath: error.instanceLocation.replace(/^#/, ''),
        keyword: error.keyword,
        params: extractParams(error),
    };

    if (error.keyword === 'not') {
        // the form tells "not" constraints apart by their subschema
        mappedError.schema = resolveKeywordValue(rootSchema, error.keywordLocation);
    }

    return mappedError;
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
            // cfworker annotates the schema it is given (e.g. "__absolute_uri__"), so the
            // subschemas reported with the errors are read from an untouched copy
            const originalSchema = JSON.parse(JSON.stringify(schema));
            const validator = new Validator(schema, '7', false);

            const validate = (data: mixed): boolean => {
                const {valid, errors} = validator.validate(normalizeData(data));

                validate.errors = valid
                    ? null
                    : errors
                        .filter((error) => !APPLICATOR_KEYWORDS.has(error.keyword))
                        .map((error) => mapError(error, originalSchema));

                return valid;
            };

            validate.errors = null;

            return validate;
        },
    };
};

export default createValidator;
