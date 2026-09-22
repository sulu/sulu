// @flow
import createBlockIdBackfiller, {readBlockIdGeneratorOption} from '../blockIdBackfill';
import blockIdGenerator from '../blockIdGenerator';

beforeEach(() => {
    jest.restoreAllMocks();
});

test('readBlockIdGeneratorOption returns the configured boolean', () => {
    expect(readBlockIdGeneratorOption({block_id_generator: {value: true}}, 'block')).toEqual(true);
    expect(readBlockIdGeneratorOption({block_id_generator: {value: false}}, 'block')).toEqual(false);
    expect(readBlockIdGeneratorOption({}, 'block')).toEqual(undefined);
});

test('readBlockIdGeneratorOption throws for a non-boolean option', () => {
    expect(() => readBlockIdGeneratorOption({block_id_generator: {value: 'yes'}}, 'image_map'))
        .toThrow('image_map');
});

test('does nothing when the value getter is not a function', async() => {
    const generateSpy = jest.spyOn(blockIdGenerator, 'generateBlockIds');
    const backfill = createBlockIdBackfiller(jest.fn());

    // $FlowFixMe intentionally passing the wrong type
    await backfill(undefined, {editor: {}});

    expect(generateSpy).not.toHaveBeenCalled();
});

test('does not call onFilled when nothing is missing', async() => {
    jest.spyOn(blockIdGenerator, 'countMissingBlockIds').mockReturnValue(0);
    const generateSpy = jest.spyOn(blockIdGenerator, 'generateBlockIds');
    const onFilled = jest.fn();
    const backfill = createBlockIdBackfiller(onFilled);

    await backfill(() => [{_id: 'a', type: 'editor'}], {editor: {}});

    expect(generateSpy).not.toHaveBeenCalled();
    expect(onFilled).not.toHaveBeenCalled();
});

test('merges the generated ids into the value that is current when the response arrives', async() => {
    // The getter returns the value the user is still typing, not the snapshot the run started with.
    let current = [{type: 'editor', text: ''}];

    let resolveIds: (ids: Array<string>) => void = () => {};
    jest.spyOn(blockIdGenerator, 'countMissingBlockIds').mockReturnValue(1);
    jest.spyOn(blockIdGenerator, 'generateBlockIds').mockReturnValue(new Promise((resolve) => {
        resolveIds = resolve;
    }));
    jest.spyOn(blockIdGenerator, 'applyBlockIds').mockImplementation((value, types, ids) => {
        const clone = value.map((item) => ({...item}));
        clone[0]._id = ids[0];

        return clone;
    });

    const onFilled = jest.fn();
    const backfill = createBlockIdBackfiller(onFilled);

    const run = backfill(() => current, {editor: {}});

    // text typed after the request went out but before the ids came back
    current = [{type: 'editor', text: 'typed while generating'}];

    resolveIds(['generated-id']);
    await run;

    expect(blockIdGenerator.applyBlockIds).toHaveBeenCalledWith(
        [{type: 'editor', text: 'typed while generating'}],
        {editor: {}},
        ['generated-id']
    );
    expect(onFilled).toHaveBeenCalledWith([{type: 'editor', text: 'typed while generating', _id: 'generated-id'}]);
});

test('serialises overlapping runs and re-runs once with the latest value', async() => {
    const countSpy = jest.spyOn(blockIdGenerator, 'countMissingBlockIds')
        .mockReturnValueOnce(1)
        .mockReturnValue(0);
    let resolveIds: (ids: Array<string>) => void = () => {};
    const generateSpy = jest.spyOn(blockIdGenerator, 'generateBlockIds').mockReturnValue(new Promise((resolve) => {
        resolveIds = resolve;
    }));
    jest.spyOn(blockIdGenerator, 'applyBlockIds').mockReturnValue([{_id: 'x', type: 'editor'}]);

    const onFilled = jest.fn();
    const backfill = createBlockIdBackfiller(onFilled);

    const first = backfill(() => [{type: 'editor'}], {editor: {}});
    // second call arrives while the first is still generating and must not start a parallel request
    backfill(() => [{type: 'editor'}], {editor: {}});

    expect(generateSpy).toHaveBeenCalledTimes(1);

    resolveIds(['x']);
    await first;

    // the queued run fires once, sees nothing missing, and issues no further request
    expect(generateSpy).toHaveBeenCalledTimes(1);
    expect(countSpy).toHaveBeenCalledTimes(2);
});
