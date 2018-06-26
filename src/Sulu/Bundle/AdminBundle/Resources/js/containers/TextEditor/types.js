// @flow
export type TextEditorProps = {
    onBlur?: () => void,
    onChange: (string) => void,
    placeholder?: string,
    valid: boolean,
    value: ?string,
};
