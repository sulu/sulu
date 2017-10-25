/* eslint-disable flowtype/require-valid-file-annotation */
import afterElementsRendered from '../afterElementsRendered';

test('The function should call its passed callback', (done) => {
    afterElementsRendered(done);
});
