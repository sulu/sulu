// @flow
import afterElementsRendered from '../afterElementsRendered';

test('The function should call its passed callback', (done) => {
    expect.assertions(1);

    const callback = jest.fn(() => {
        expect(callback).toHaveBeenCalledTimes(1);
        done();
    });

    afterElementsRendered(callback);
});
