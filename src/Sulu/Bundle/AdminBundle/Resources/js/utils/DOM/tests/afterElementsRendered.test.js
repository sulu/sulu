// @flow
import afterElementsRendered from '../afterElementsRendered';

test('The function should call its passed callback', (done) => {
    afterElementsRendered(done);
});
