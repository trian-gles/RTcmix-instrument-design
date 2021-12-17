<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<title>RTcmix - Tutorials - Creating an RTcmix Instrument</title>
	
	<link rel="stylesheet" type="text/css" href="/includes/style.css">
	
</head>
<body>
	
<?php include($_SERVER['DOCUMENT_ROOT'].'/includes/head.inc'); ?>

<h1>Creating an RTcmix Instrument</h1>

<i>[Updated for v. 4.5]</i>
<p>

Most RTcmix instruments are designed to
inherit from the <i>Instrument</i> class -- see the
<a href="/reference/design/Instrument.php">Instrument class</a>
documentation for a more-or-less complete listing of variables
and associated functions for this class.  Don't be fooled by
the relative complexity of the <i>Instrument</i> class, however.
As an instrument designer, you are only required to implement
two member functions for your instrument.  You will need
to set up your instrument by filling out the <i>init()</i>
function, and you will need to write your sample-computing code
in the <i>run()</i> member function.  That's all!
<p>
When RTcmix runs, it first calls a particular
instrument's <i>init()</i> function whenever it detects that the
instrument command has been parsed.  The <i>init()</i> function
then initializes various parameters, including setting the start
time and end time for the note of that instrument on
the RTcmix scheduled execution queue (called "the heap").
When RTcmix starts running "the heap" and a note start is encountered,
then the <i>run()</i> function for that instrument
is called repeatedly to generate samples for output.  Although
RTcmix calls the instrument/note's <i>run()</i> function
in chunks corresponding to the <i>bufsamps</i> size that
can be set via
<a href="/reference/scorefile/rtsetparams.php">rtsetparams</a>
scorefile command, when you design an instrument you can safely
assume that the processing of samples in the <i>run()</i> function
will be continuous.
<p>
Sounds tricky?  Actually, it's not too difficult at all.  To show
how all this works, we'll design a simple oscillator instrument
and then progressively add features to build a more complex
signal-processing amplitude modulation (AM) instrument.

<h2>A Simple Oscillator Instrument</h2>

What we're planning to do is to create a limited version of the
<a href="/reference/instruments/WAVETABLE.php">WAVETABLE</a>
instrument.  WAVETABLE works by reading a waveform created by the
<a href="/reference/scorefile/maketable.php">maketable</a>
scorefile command.
<i>[note: In addition to the </i>maketable<i> command documentation, click
<a href="http://rtcmix.org/tutorials/pfields.php">here</a>
for a short discussion of how </i>maketable<i> can be used in a scorefile
to create a waveform.]</i>
<p>
WAVETABLE, and hence our simple oscillator instrument, works by
copying values from the <i>maketable</i>-created table handle passed //FIX THIS
to the output in such a manner that a repeating (oscillating!)
waveform with a specific frequency, amplitude and duration is
heard when the samples are converted to sound.
<p>
This means that we have to make some decisions about how to
control the parameters of our simple oscillator.  We'll define <i>p-fields</i> (parameters)
for the start time, the duration, the amplitude, the pitch, and our wavetable <i>table-handle</i>.
We'll simply do them in that order (remember that in C++ numbering
starts from 0):
<ul>
	p-field 0 (p0) == start time (in seconds)
	<br>
	p-field 1 (p1) == duration (in seconds)
	<br>
	p-field 2 (p2) == amplitude (we'll use direct, 16-bit
	amplitude values, 0-32768)
	<br>
	p-field 3 (p3) == pitch (we'll use Hz (cycles/second))
	<br>
	p-field 4 (p4) == wavetable <i>table-handle</i>
</ul>
We're also going to stipulate that the instrument will write nothing
but 2-channel (stereo) output sound, with the amplitude being
equal in both channels. <i>[note:  By convention, p0 is generally
the start time for an RTcmix instrument.  p1 is usually the duration, unless
the instrument is a signal-processing instrument.  Then p1 represents
the amount to skip on an input soundfile for processing, and p2 then
becomes the duration parameter.  The rest of the p-fields are
deteremined by the instrument designer.]</i>
<p>
FInally, we need to decide what to call our instrument.  SIMPLEOSC
seems like an appropriate, although somewhat uninspired name.

<a name="template"></a>

<h2>Setting up the Template</h2>

If we had to write <u>everything</u> in an RTcmix instrument
from scratch, it would be tedious and difficult indeed.  Fortunately // REMAKE A TEMPLATE FILE
there is a Better Way.  In the "RTcmix/docs/sample_code" directory
(in the RTcmix distribution) as a directory titled "TEMPLATE"
(click
<a href="TEMPLATE.tar.gz">here</a>
to download a "TEMPLATE.tar.gz" file that will unpack to this directory).
Copy the entire "TEMPLATE" directory over to the location where
you plan to work on building SIMPLEOSC.  Rename the "TEMPLATE"
directory "SIMPLEOSC".  Inside the "SIMPLEOSC" directory
you should still see files like "TEMPLATE.cpp" and "TEMPLATE.h".
Follow these easy-as-pie directions, and you will be ready
to go to work on the SIMPLEOSC instrument:
<ul>
	1.  Rename the files "TEMPLATE.cpp" and "TEMPLATE.h" to
	"SIMPLEOSC.cpp" and "SIMPLEOSC.h"
	<br>
	2.  Edit the file "SIMPLEOSC.cpp" and change <u>every</u>
	occurence of the word <i>TEMPLATE</i> to <i>SIMPLEOSC</i>.
	<ul>
	<i>[note:  We are assuming the use of a text-based editor,
	not a word-processing-type application that will embed
	formatting information into the file.  No .rtf or .doc
	files -- only plain old generic ASCII text, please!]</i>
	</ul>
	3.  Edit the file "SIMPLEOSC.h" and change <u>every</u>
	occurence of the word <i>TEMPLATE</i> to <i>SIMPLEOSC</i>.
	<br>
	4.  Edit the file "Makefile" and change the line:
<pre>
       NAME = TEMPLATE
</pre>
to
<pre>
       NAME = SIMPLEOSC
</pre>
</ul>
You are now set to design and build SIMPLEOSC.

<h2>The SIMPLEOSC::init() Member Function</h2>

Edit the file "SIMPLEOSC.cpp" again.  Locate the definition of
the <i>SIMPLEOSC::init()</i> function (it should be about line 15 in the
file).  Note that <i>init()</i> is passed two variables -- the first is
an array of floating-point numbers (<i>p[]</i>) and the second is
an integer variable (<i>n_args</i>).  The values in these
variables is determined by the scorefile.  If RTcmix parses the
following line in a scorefile:
<pre>
       SIMPLEOSC(0, 3.5, 20000, 478.0, wavetable)
</pre>
then the <i>SIMPLEOSC::init()</i> function will be called with
<i>n_args</i> set to 5 and the <i>p[]</i> array set to these values:
<pre>
       p[0] = 0.0;
       p[1] = 3.5;
       p[2] = 20000.0;
       p[3] = 478.0;
       p[4] = the <table-handle> defined with the name <i>wavetable</i>
</pre>
This is how data passes into instruments for particular notes from
an RTcmix scorefile.  Note that all of the numerical p-field parameters are
converted to floating-point, even if they were entered as integers
in the scorefile.  That's just the way it is.
<p>
We won't be using <i>n_args</i> in SIMPLEOSC, but it is useful
for checking how many p-fields are actually present for
error-checking, setting up optional p-fields, etc.  The default
maximum p-fields in RTcmix is set at 1024.  This can be changed
by finding the definition for <i>MAXDISPARGS</i> in the file
"RTcmix/H/mixdispargs.h", changing it to the desired value and
recompiling RTcmix.
<p>
The first thing we need to do is to set the start time and
duration for the instrument/note.  This is done using the
<a href="/reference/design/rtsetoutput.php">rtsetoutput</a>
function.  <i>rtsetoutput()</i> takes 3 parameters, a starting
time, a duration (both in seconds), and a pointer to the instrument
being called (represented in C++ by the variable <i>this</i>.
<i>p[0]</i> is our start time and <i>p[1]</i>
is our duration, so the first addition we will make to the
<i>SIMPLEOSC::init()</i> function will be:
<pre>
       int SIMPLEOSC::init(float p[], int n_args)
       {
              // p0 = start, p1 = duration

              rtsetoutput(p[0], p[1], this);

       ...
</pre>
This is such a common operation in RTcmix instrument design
that we have already set it up in our TEMPLATE instrument
directory.
<p>
Next we need to store the amplitude into a variable that we can access
during the <i>SIMPLEOSC::run()</i> function, a variable that we will
use to multiply the sample values we generate to reach a specified amplitude.
We will call the variable <i>amp</i> and the addition to the
<i>SIMPLEOSC::init()</i> function to transfer the value from <i>p[2]</i>
to the variable is trivial:
<pre>
       int SIMPLEOSC::init(float p[], int n_args)
       {
              // p0 = start, p1 = duration, p2 = amplitude

              rtsetoutput(p[0], p[1], this);

              amp = p[2];

       ...
</pre>
<i>amp</i>, however, is not an <i>Instrument</i> class variable, so
we need to declare it.  Since <i>amp</i> will be used in both the
<i>SIMPLEOSC::init()</i> and <i>SIMPLEOSC::run()</i> functions, we
need to declare it so that both of these SIMPLEOSC functions
have access to it.
This is done by putting the declaration for <i>amp</i> in
the "SIMPLEOSC.h" header file:
<pre>
       class SIMPLEOSC : public Instrument {
              float amp;

       public:
              SIMPLEOSC();

       ...
</pre>Notice that <i>amp</i> is declared as type "float".  Sample values
in RTcmix are indeed floating-point ("float") numbers; since <i>amp</i>
will be used to multiply samples, then it makes sense to declare
it as the same type.
<p>
Next, we need to take our wavetable <i>table handle</i> and store a reference
to its underlying array.  First let's add another declaration to the "SIMPLEOSC.h"
header file:
<pre>
       class SIMPLEOSC : public Instrument {
              float amp;
              double* wavetable;

       public:
              SIMPLEOSC();

       ...
</pre> We can obtain this underlying array by using the getPFieldTable function, 
which takes the index of our <i>table-handle</i> in the p-field array, and a reference
to an integer which will be used to store the length of the wavetable array.
<pre>


       int SIMPLEOSC::init(float p[], int n_args)
       {
              // p0 = start, p1 = duration, p2 = amplitude, p3 = frequency, p4 = wavetable

              nsamps = rtsetoutput(p[0], p[1], this);

              amp = p[2];

	      int tablelen = 0;

	      wavetable = (double *) getPFieldTable(4, &tablelen);

      ...
</pre>All that is left for us to do in initializing our instrument
is to set up a wavetable oscillator to work with the proper
frequency <i>p[3]</i>and the proper waveform (now stored in the variable wavetable).
In the Bad Old Days, this used to be rather annoying,
involving calculations of a weird thing called a sampling
increment and strange setups of variables for the phase of
the oscillator, etc. (you will still run into this kind of code
in many of the RTcmix instruments included in the distribution).
RTcmix now provides a handy object,
<a href="/reference/design/Ooscili.php">Ooscili</a>
that makes our job much easier.  All we have to do is instantiate
the object with the sampling rate, desired frequency, wavetable 
<i>table-handle</i>, and length of said table:
<pre>
       int SIMPLEOSC::init(float p[], int n_args)
       {
              // p0 = start, p1 = duration, p2 = amplitude, p3 = frequency, p4 = wavetable

              rtsetoutput(p[0], p[1], this);

              amp = p[2];

	      int tablelen = 0;

	      wavetable = (double *) getPFieldTable(4, &tablelen);

              theOscil = new Ooscili(SR, p[3], wavetable, tablelen);

       ...
</pre>
<i>Ooscili</i> takes a floating-point value in Hz for frequency, and
an integer for the function-table slot of the waveform to use.  Of course,
we have to declare <i>theOscil</i>, this is also done in "SIMPLEOSC.h"
because it will be used in both
<i>SIMPLEOSC::init()</i> and <i>SIMPLEOSC::run()</i>:
<pre>
       class SIMPLEOSC : public Instrument {
              float amp;
              Ooscili *theOscil;

       public:
              SIMPLEOSC();

       ...
</pre>
All we have to do now is to return how many sample frames we need //EXPLAIN WHAT nSAMPS() DOES
to compute for the note. <i>[note:  a sample <u>frame</u> corresponds to
one 'sample' of time, irregardless of how many channels are
in the output.  For a 1-channel output, this is just the total
number of samples to be computed.  For a stereo output, this
is 1/2 the total number of samples to be computed, because we need
to compute 2 samples for each 'sample' of time.  This is not something
you will necessarily have to worry about.]</i> Our definition of the 
<i>SIMPLEOSC::init()</i> is now complete:
<pre>
       int SIMPLEOSC::init(float p[], int n_args)
       {
              // p0 = start, p1 = duration, p2 = amplitude, p3 = frequency p4 = wavetable

              rtsetoutput(p[0], p[1], this);

              amp = p[2];

	      int tablelen = 0;

	      wavetable = (double *) getPFieldTable(4, &tablelen);

              theOscil = new Ooscili(SR, p[3], wavetable, tablelen);

              return nSamps();
       }
</pre>


<h2>The SIMPLEOSC::run() Member Function</h2>

Now that we've set the initialization of our SIMPLEOSC
instrument, we need to create the sample-computing part in the
<i>SIMPLEOSC::run()</i> function.  There are several items
that we will need to establish in this function that have
already been set up in the TEMPLATE directory.  The first is
that we will need to create a loop that will generate one sample
value every time it gets iterated.  This appears as the skeleton
for a <i>for</i> loop already in the <i>SIMPLEOSC::run()</i> function:
<pre>
              for (i = 0; i < framesToRun(); i++) {
              ...
              }
</pre>
The variable <i>i</i> has already been declared (locally, it
is only used in the <i>SIMPLEOSC::run()</i> function) and
the <i>frameToRun()</i> function will return how many samples
we need to compute for our note.
<p>
The second item is that we need to let RTcmix keep track of how
many samples we have computed for our instrument/note.  This is
done with the <i>increment()</i> function inside the sample-computing
<i>for</i> loop.
Our <i>SIMPLEOSC::run()</i> function now looks like this:
<pre>
       int SIMPLEOSC::run()
       {
              int i;

              for (i = 0; i < framesToRun(); i++) {

              ...
                     increment();
              }

       ...
</pre>
All we need to do now is to get our <i>Ooscili</i> object
(<i>theOscil</i> in our SIMPLEOSC instrument) to generate
samples within the <i>for</i> sample-computing loop.  So easy!  We need
to declare a variable that will temporarily store the sample values
prior to sending them out to the Real World.  For this task we will
use an array with 2 elements: <i>out[2]</i>.  It will be a floating-point
array, of course, since we will be using it for samples, and
<i>out[0]</i> will hold the value for channel 0 ("left") and
and <i>out[1]</i> will hold the value for channel 1 ("right).
<p>
The way to get <i>theOscil</i> to produce samples is to use
the <i>next()</i> member function of the <i>Ooscili</i>
object.  <i>Ooscili</i> puts out sample values between
-1.0 and 1.0, so we want to scale them (multiply them) by
<i>amp</i> in order to get our desired amplitude.
Putting this all together, we have the following:
<pre>
       int SIMPLEOSC::run()
       {
              int i;
              float out[2];

              for (i = 0; i < framesToRun(); i++) {
                     out[0] = theOscil->next() * amp;
                     out[1] = out[0];

                     increment();
              }

       ...
</pre>
Why did we say "out[1] = out[0]"?  Why didn't we do:
<pre>
                     out[0] = theOscil->next() * amp;
                     out[1] = theOscil->next() * amp;
</pre>
The problem with the above is that <u>every</u> time the <i>next()</i>
function is called, it generates the next sample in the evolving
waveform.  By assigning it once to <i>out[0]</i> and then
moving to the next sample and assigning it to <i>out[1]</i>, we
will have effectively split the waveform between the two channels, generating
samples twice as fast as we actually wanted.  The sonic result is a sound
twice the frequency we desired, with a bit of grunginess potentially
thrown in to boot.
<p>
Why even bother with assiging sample values to the <i>out</i>
array anyhow?  This is because we use <i>out</i> to shuttle samples
into our output stream that gets sent to the digital-to-analog
convertors in the computer.  We do this by using the
<a href="/reference/design/rtaddout.php">rtaddout</a>
function:
<pre>
                     rtaddout(out);
</pre>
Guess what?  We're done!  We've now designed a fully-fledged-and-functional
RTcmix instrument.  Adding a return value to the
<i>SIMPLEOSC::run()</i> function, our whole listing of
<i>SIMPLEOSC::init()</i> and <i>SIMPLEOSC::run()</i> looks like
this:
<pre>
       int SIMPLEOSC::init(float p[], int n_args)
       {
              // p0 = start, p1 = duration, p2 = amplitude, p3 = frequency

              nsamps = rtsetoutput(p[0], p[1], this);

              amp = p[2];

              theOscil = new Ooscili(p[3], 2);

              return(nsamps);
       }


       int SIMPLEOSC::run()
       {
              int i;
              float out[2];

              for (i = 0; i < framesToRun(); i++) {
                     out[0] = theOscil->next() * amp;
                     out[1] = out[0];

                     rtaddout(out);

                     increment();
              }

              return i;
       }
</pre>
The corresponding declarations in the "SIMPLEOSC.h" file
are:
<pre>
       class SIMPLEOSC : public Instrument {
              float amp;
              double* wavetable;
              Ooscili *theOscil;

       public:
              SIMPLEOSC();

       ...
</pre>
All of the other text in the "SIMPLEOSC.C" and "SIMPLEOSC.h" files
should be left intact -- all of the <i>#include</i>'s, the function
declarations for <i>makeSIMPLEOSC()</i>, etc.  These are needed
by RTcmix to properly build the instrument.


<h2>Compiling and Running <i>SIMPLEOSC</i></h2>

We've already modified the Makefile to compile <i>SIMPLEOSC</i>.
All you should have to do at this point is say:
<pre>
       make
</pre>
and the Makefile should build the dynamically-loaded instrument
library "libSIMPLEOSC.so".  Be sure that the file "package.conf"
is set to list your installation of RTcmix.  The default contents
of this file:
<pre>
       include /usr/local/src/RTcmix/makefile.conf
</pre>
will work if in fact you have installed RTcmix in "/usr/local/src/RTcmix".
<p>
Next we need to create a scorefile to run SIMPLEOSC.  Given the small
number of p-fields and constraints we have placed on the parameters in
the design of our instrument, this scorefile is very simple, indeed.
We'll create a text file, "S1.sco", with the following contents:
<pre>
       rtsetparams(44100, 2)
       load("./libSIMPLEOSC.so")

       makegen(2, 10, 1000, 1.0, 0.3, 0.1)
       SIMPLEOSC(0, 3.5, 20000, 387.14)
</pre>
Notice that the <i>load</i> scorefile command is instructed to load
<i>"./libSIMPLEOSC.so"</i> instead of <i>"SIMPLEOSC"</i>.  This
signals <i>load</i> to search in the local directory ("./")
instead of the default main RTcmix shared library
("/usr/local/src/RTcmix/shlib").
<p>
Running the CMIX command with this score:
<pre>
	CMIX < S1.sco
</pre>
should yield a Glorious 387.14 Hz sound with an amplitude of 20000 and
a duration of 3.5 seconds.  Yay!

<h2>Adding an Amplitude Envelope</h2> //START FROM HERE NEXT

As much fun as SIMPLEOSC is, the start and end of each note is a bit
stark.  Our next step is to add an amplitude envelope, or a way of
fading up and down the sample amplitude as we generate each sample.
Here is the code in the <i>SIMPLEOSC::run()</i> member function
that does in fact produce the samples:
<pre>
                     out[0] = theOscil->next() * amp;
</pre>
What we need to do is obvious -- we have to find a way of dynamically
altering <i>amp</i> to accomplish the fade-up/fade-down.
<p>
One approach would be to install some simple math that would accompish
this, but there is another approach that will give us a lot more flexibility
in designing complicated amplitude envelopes.  Recall that <i>amp</i>
is set to be the maximum sample amplitude that we want to generate for
each note (using the 0-32768 16-but integer scale).  If we can
generate an additional multiplier that tracks a curve from 0 to 1, then
we can use that to modify the overall value of <i>amp</i>.  Finding
a way to generate a 0-1 curve of some kind will give us a very powerful tool
to build an amplitude envelope.
<p>
<a href="/reference/scorefile/makegen.php">makegen</a>
to the rescue again!  A number of <i>makegen</i> routines are in fact
designed to do exactly this.
<a href="/reference/scorefile/gen24.php">gen24</a>,
<a href="/reference/scorefile/gen18.php">gen18</a>,
<a href="/reference/scorefile/gen4.php">gen4</a>,
<a href="/reference/scorefile/gen5.php">gen5</a>,
<a href="/reference/scorefile/gen6.php">gen6</a> and
<a href="/reference/scorefile/gen7.php">gen7</a>
can all be used to make curves between 0.0 and 1.0 of almost arbitrary
complexity.  All we need to do is read the values from these
<i>makegen</i>-created functions and multiply them by the <i>amp</i>
variable.
<p>
We already know how to do this -- use the <i>Ooscili</i> object.
The trick is to set it up so that the amplitude envelope "oscillator"
will only "oscillate" once during the note duration.  This is easy, just
set the oscillator frequency to <i>1.0/duration</i> (wavelength [duration]
and frequency are reciprocals).
<p>
So in the "SIMPLEOSC.h" header file we declare another <i>Ooscili</i>
variable:
<pre>
       class SIMPLEOSC : public Instrument {
              float amp;
              Ooscili *theOscil;
              Ooscili *theEnv;

       public:
              SIMPLEOSC();

       ...
</pre>
and we initialize it in the <i>SIMPLEOSC::init()</i> member function:
<pre>
       int SIMPLEOSC::init(float p[], int n_args)
       {
              // p0 = start, p1 = duration, p2 = amplitude, p3 = frequency

              nsamps = rtsetoutput(p[0], p[1], this);

              amp = p[2];

              theOscil = new Ooscili(p[3], 2);
              theEnv = new Ooscili(1.0/p[1], 1);

              return(nsamps);
       }
</pre>
Notice that we are using function-table slot #1 for our amplitude envelope
curve.  We originally chose function-table slot #2 as our oscillator
waveform to allow this.  Amplitude envelopes in RTcmix are typically stored
in function-table slot #1 (the
<a href="/reference/scorefile/setline.php">setline</a>
scorefile command expects this).
<p>
Using <i>theEnv</i> to shape our amplitude evolution is trivial:
<pre>
                     out[0] = theOscil->next() * amp * theEnv->next();
</pre>
So our modified SIMPLEOSC now looks like this:
<pre>
       int SIMPLEOSC::init(float p[], int n_args)
       {
              // p0 = start, p1 = duration, p2 = amplitude, p3 = frequency

              nsamps = rtsetoutput(p[0], p[1], this);

              amp = p[2];

              theOscil = new Ooscili(p[3], 2);
              theEnv = new Ooscili(1.0/p[1], 1);

              return(nsamps);
       }


       int SIMPLEOSC::run()
       {
              int i;
              float out[2];

              Instrument::run();

              for (i = 0; i < framesToRun(); i++) {
                     out[0] = theOscil->next() * amp * theEnv->next();
                     out[1] = out[0];

                     rtaddout(out);

                     increment();
              }

              return i;
       }
</pre>
The declarations in the "SIMPLEOSC.h" file are:
<pre>
       class SIMPLEOSC : public Instrument {
              float amp;
              Ooscili *theOscil;
              Ooscili *theEnv;

       public:
              SIMPLEOSC();

       ...
</pre>
<p>
Compiling this code will give you a versatile and powerful SIMPLEOSC,
indeed.  How versatile and powerful?  Theoretically, armed with a sine
wave and the amplitude controls in the modified SIMPLEOSC,
you should be able to recreate 
<a href="http://www.sosmath.com/fourier/fourier1/fourier1.html">any sound
imaginable!</a>.
Of course, theory and practice are two different things...
<p>
Seriously, you can create some wonderful sounds using SIMPLEOSC.
Used with the
<a href="/reference/scorefile/">RTcmix scorefile</a>
capabilities, SIMPLEOSC can build interesting granular-synthesis
textures.  Try the following scorefile using SIMPLEOSC just for
fun:
<pre>
       rtsetparams(44100, 2)
       load("./libSIMPLEOSC.so")

       makegen(1, 24, 1000, 0.0,0.0, 0.1,1.0, 0.2,0.0)
       makegen(2, 10, 1000, 1.0, 0.3, 0.1)

       start = 0.0
       basefreq = 200.0
       for (i = 0; i < 1500; i=i+1)
       {
              freq = irand(basefreq, basefreq+300)
              SIMPLEOSC(start, 0.2, 4000, freq)

              start = start + 0.01
              basefreq = basefreq + 2.0
       }
</pre>

<h2>Reading an Input Soundfile</h2>

Our SIMPLEOSC instrument can be changed into a signal-processing
instrument with a few minor changes.  By signal-processing, we mean
that it will operate upon an input sound instead of generating a sound
'from scratch'.  We'll call this instrument SIMPLEMIX -- we can
set it up the same way we did for SIMPLEOSC, using the directions
for
<a href="#template"><b>Setting up the Template</b></a>
(using SIMPLEMIX in place of SIMPLEOSC
or we can just modify SIMPLEOSC so that it becomes SIMPLEMIX.
<p>
In any case, we want to take out the references to the sample-generating
<i>Ooscili</i> object <i>theOsc</i>, but keep the amplitude <i>Ooscili</i>
(<i>theEnv</i>).  In place of <i>theOscil</i>, we will set up
a soundfile for reading using
<a href="/reference/design/rtsetinput.php">rtsetinput</a>.
<i>rtsetinput</i> works like
<a href="/reference/design/rtsetoutput.php">rtsetoutput</a>,
but it takes only a <i>start time</i> parameter (along with the
<i>Instrument*</i> pointer) instead of a <i>start time</i> and
<i>duration</i> parameter.  This is because the duration is established
by <i>rtsetoutput</i>, having <i>rtsetinput</i> also do it would
be redundant.  Thus <i>rtsetinput</i> does not return the number of
sample frames to be computed like <i>rtsetoutput</i>.  Instead, it
returns "0" if it successfully opens the input, or "-1" if it
doesn't.  This is useful for checking if a soundfile was opened
properly, or if there was some error (perhaps the name of the soundfile
was mis-typed).  Generally errors will be caught in opening the
input soundfile, accomplished through the most recent call to the
<a href="/reference/scorefile/rtinput.php">rtinput</a>
scorefile command (this establishes what <i>rtsetinput</i> will read).
<p>
The <i>start time</i> parameter for <i>rtsetinput</i> refers to
the time-point (in seconds) to start reading the input soundfile.
This allows us to skip into the input soundfile by a specified amount
to process a particular patch of sound.  This parameter is ignored
if the controlling scorefile for the instrument
sets up real-time ("live") input, as it is difficult
to skip arbitrarily into the future.  It would probably be fun, though.
<p>
<p>
We'll modify our p-fields to accommodate this new input-skipping
parameter.  We also need to initialize an
<a href="/reference/design/Ortgetin.php">Ortgetin</a>
object to deliver samples into our <i>SIMPLEMIX::run()</i>
method.  As with <i>theEnv</i> (and <i>theOscil</i> in SIMPLEOSC),
we will declare this in our "SIMPLEMIX.h" file.
<p>
With these changes, our finished <i>SIMPLEMIX::init()</i>
member function becomes:
<pre>
       int SIMPLEMIX::init(float p[], int n_args)
       {
              // p0 = start, p1 = input skip, p2 = duration, p3 = amplitude

              nsamps = rtsetoutput(p[0], p[2], this);
              rtsetinput(p[1], this); // we're being bad, not checking for errors
              theIn = new Ortgetin(this);

              amp = p[3];

              theEnv = new Ooscili(1.0/p[2], 1);

              return(nsamps);
       }
</pre>
and "SIMPLEMIX.h" is:
<pre>
       class SIMPLEMIX : public Instrument {
              float amp;
              Ortgetin *theIn;
              Ooscili *theEnv;

       public:
              SIMPLEMIX();

       ...
</pre>
The <i>SIMPLEMIX::run()</i> is then easy to code.  All we have to do is
declare an input array that we will use to hold our incoming samples
for each frame, reading new samples into it using the <I>Ortgetin::next()</i>
method:
<pre>
       int SIMPLEMIX::run()
       {
              int i;
              float out[2];
              float in[2];
              float aamp;

              Instrument::run();

              for (i = 0; i < framesToRun(); i++) {
                     theIn->next(in);
                     aamp = amp * theEnv->next();
                     out[0] = in[0] * aamp;
                     out[1] = in[1] * aamp;

                     rtaddout(out);

                     increment();
              }

              ...
</pre>
Notice that we are using the variable <i>aamp</i> to store the envelope-shaped
amplitude.  This is to avoid calling <i>theEnv->next()</i> more than
once for each sample frame, and it also slightly increases the efficiency
of the instrument.  As with SIMPLEOSC,
also be aware that we have made some assumptions about
the nature of our input and output soundfiles or devices -- they both
need to be at least two-channels.  This instrument will not work properly
for mono soundfiles!  If your intention is to write a more general-purpose
instrument capable of handling mono/stereo/whatever soundfiles, then
you will need to add code to check for and handle differing channel
configurations.  <i>Ortgetin</i> does not do this for you.
<p>
Given these caveats, our finished SIMPLEMIX instrument is:
<pre>
       int SIMPLEMIX::init(float p[], int n_args)
       {
              // p0 = start, p1 = input skip, p2 = duration, p3 = amplitude

              nsamps = rtsetoutput(p[0], p[2], this);
              rtsetinput(p[1], this); // we're being bad, not checking for errors
              theIn = new Ortgetin(this);

              amp = p[3];

              theEnv = new Ooscili(1.0/p[2], 1);

              return(nsamps);
       }

       int SIMPLEMIX::run()
       {
              int i;
              float out[2];
              float in[2];
              float aamp;

              Instrument::run();

              for (i = 0; i < framesToRun(); i++) {
                     theIn->next(in);
                     aamp = amp * theEnv->next();
                     out[0] = in[0] * aamp;
                     out[1] = in[1] * aamp;

                     rtaddout(out);

                     increment();
              }

              return i;
       }
</pre>
As with SIMPLEOSC, we can use SIMPLEMIX to create some nifty granular
effects.  The following scorefile, for example:
<pre>
       rtsetparams(44100, 2)
       load("./libSIMPLEMIX.so")

       rtinput("/snd/somesound.aiff")

       makegen(1, 24, 1000, 0.0, 0.0, 0.1, 1.0, 0.2, 0.0)

       totaldur = DUR()
       start = 0.0

       for (i = 0; i < 100; i = i+1)
       {
              inskip = irand(0.0, (totaldur-0.2))
              SIMPLEMIX(start, inskip, 0.2, 1)
              start = start + 0.1
       }
</pre>
will randomly grab little chunks of sound throughout a soundfile, while this
scorefile:
<pre>
       rtsetparams(44100, 2)
       load("./libSIMPLEMIX.so")

       rtinput("/snd/somesound.wav")

       makegen(1, 25, 1000, 1)

       start = 0.0
       inskip = 0.0

       for (i = 0; i < 500; i = i+1)
       {
              SIMPLEMIX(start, inskip, 0.1, 1)
              start = start + 0.05
              inskip = inskip + 0.02
       }
</pre>
will do a rough granular time-stretching of the original sound.


<?php include($_SERVER['DOCUMENT_ROOT'].'/includes/foot.inc'); ?>

