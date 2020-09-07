// @flow
import initializer from '../../../services/initializer';

export default function(): {[string]: any} {
    return {__bundles: initializer.bundles};
}
