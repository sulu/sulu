// @flow
import {toJS} from 'mobx';
import FlatStrategy from '../../structureStrategies/FlatStrategy';

test('Should be empty after intialization', () => {
    const flatStrategy = new FlatStrategy();
    expect(toJS(flatStrategy.data)).toEqual([]);
});

test('Should be empty after clear was called', () => {
    const flatStrategy = new FlatStrategy();
    flatStrategy.data = [{id: 1}];

    flatStrategy.clear();
    expect(toJS(flatStrategy.data)).toEqual([]);
});
