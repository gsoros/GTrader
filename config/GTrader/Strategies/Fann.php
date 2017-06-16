<?php

return [
    'signals_indicator_class'       => 'FannSignals',
    'prediction_indicator_class'    => 'FannPrediction',
    'path'                          => env('FANN_PATH', storage_path('fann')),
    'sample_size'                   => env('FANN_SAMPLE_SIZE', 5),          // # candles to sample for input
    'inputs'                        => ['open', 'high', 'low', 'close'],    // FANN input fields
    'fann_type'                     => 'fixed',                             // 'fixed' or 'cascade'
    'hidden_array'                  => [60, 30, 30, 15],                    // # neurons in hidden layers
    'target_distance'               => 2,                                   // prediction distance in candles
        /* Output scaling of 1.0 will produce output of 1 for a 1% delta,
        0.5 will produce 1 for 2% delta, 4.0 will produce 1 for 0.25% delta  */
    'output_scaling'                => 4,
    // apply ema to the prediction
    'prediction_ema'                => 0,
     // trade only if prediction is over this fraction of candle open price
    'long_threshold'                => 200,
    // trade only if prediction is under this fraction of candle open price
    'short_threshold'               => 200,
    // do not trade if last trade was more recent than this value
    'min_trade_distance'            => 0,
    // compensate for the bias of the null sample
    'bias_compensation'             => 0,
    'training_log_prefix'           => 'fanntraining_',
];
