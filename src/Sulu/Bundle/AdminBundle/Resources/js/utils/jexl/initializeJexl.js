// @flow
import jexl from 'jexl';

// See https://github.com/TomFrost/Jexl/blob/471f167b3ae77924b8eb409ad68d47f75ac930fb/lib/grammar.js#L91
const andBinaryOpFunc = (left: any, right: any): any => {
    return left.eval().then((leftVal: any) => {
        if (!leftVal) {
            return leftVal;
        }

        return right.eval();
    });
};

// See https://github.com/TomFrost/Jexl/blob/471f167b3ae77924b8eb409ad68d47f75ac930fb/lib/grammar.js#L101
const orBinaryOpFunc = (left: any, right: any): any => {
    return left.eval().then((leftVal: any): any => {
        if (leftVal) {
            return leftVal;
        }

        return right.eval();
    });
};

const initializeJexl = () => {
    jexl.addBinaryOp('AND', 10, andBinaryOpFunc, true);
    jexl.addBinaryOp('and', 10, andBinaryOpFunc, true);
    jexl.addBinaryOp('OR', 10, orBinaryOpFunc, true);
    jexl.addBinaryOp('or', 10, orBinaryOpFunc, true);

    jexl.addTransform('length', (value: Array<*>) => value.length);
    jexl.addTransform('includes', (value: Array<*>, search) => value.includes(search));
    jexl.addTransform('values', (value: Array<*>) => Object.values(value));
};

export default initializeJexl;
