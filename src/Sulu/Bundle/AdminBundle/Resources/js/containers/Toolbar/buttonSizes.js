// @flow

const SIZE_TO_CLASSNAME = {
    small: 'is-small',
};

export default {
    getClassName(size: string): ?string {
        return SIZE_TO_CLASSNAME[size] || null;
    },
};
