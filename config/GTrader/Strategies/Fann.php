<?php

return [
    'training_class'                => 'FannTraining',
    'prediction_indicator_class'    => 'FannPrediction',
    'path'                          => env('FANN_PATH', storage_path('fann')),
    'sample_size'                   => env('FANN_SAMPLE_SIZE', 5),          // # candles to sample for input
    'fann_type'                     => 'fixed',                             // 'fixed' or 'cascade'
    'hidden_array'                  => [60, 30, 30, 15],                    // # neurons in hidden layers
    'target_distance'               => 2,                                   // prediction distance in candles
        /* Output scaling of 1.0 will produce output of 1 for a 1% delta,
        0.5 will produce 1 for 2% delta, 4.0 will produce 1 for 0.25% delta  */
    'output_scaling'                => 4,
    // apply ema to the prediction
    'prediction_ema'                => 0,
    // long signal price source
    'long_source'                   => 'open',
    // short signal price source
    'short_source'                  => 'open',
    // trigger long signal if prediction exeeds price by this percentage
    'long_threshold'                => 0.5,
    // trigger short signal if prediction is lower than price minus this percentage
    'short_threshold'               => 0.5,
    // do not trade if last trade was more recent than this value
    'min_trade_distance'            => 1,
    // compensate for the bias of the null sample
    'bias_compensation'             => 0,
    'training_log_prefix'           => 'fanntraining_',
    'history_table'                 => 'training_history',
];
