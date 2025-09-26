// @flow
import type {FieldTypeProps} from 'sulu-admin-bundle/types';

const defaultModeResolver = (props: FieldTypeProps<?string>) => {
    const {
        schemaOptions: {
            mode: {
                value: mode = 'full',
            } = {},
        },
    } = props;

    return Promise.resolve(mode);
};

/** @internal this service can be changed at any time */
class ModeResolverRegistry {
    modeResolvers: Array<(props: FieldTypeProps) => Promise<string>> = [];

    constructor() {
        this.clear();
    }

    clear() {
        this.modeResolvers = [];
    }

    add(modeResolver: (props: FieldTypeProps) => Promise<string>) {
        this.modeResolvers.unshift(modeResolver);
    }

    all(): Array<(props: FieldTypeProps) => Promise<string>> {
        return [
            ...this.modeResolvers,
            defaultModeResolver,
        ];
    }
}

export default new ModeResolverRegistry();
