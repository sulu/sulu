// @flow

const translate = jest.fn<[string, ?Object], string>((key: string) => key);
const setTranslations = jest.fn<[Object, ?string], void>();
const clearTranslations = jest.fn<[], void>();

export {
    clearTranslations,
    setTranslations,
    translate,
};
