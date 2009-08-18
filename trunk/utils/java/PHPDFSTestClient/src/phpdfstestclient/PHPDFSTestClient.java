package phpdfstestclient;

import java.io.BufferedInputStream;
import java.io.BufferedReader;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.FileWriter;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.util.ArrayList;
import java.util.Random;
import java.util.UUID;
import java.util.concurrent.atomic.AtomicInteger;
import java.util.concurrent.atomic.AtomicLong;
import org.apache.commons.httpclient.DefaultHttpMethodRetryHandler;
import org.apache.commons.httpclient.HttpClient;
import org.apache.commons.httpclient.HttpException;
import org.apache.commons.httpclient.HttpStatus;
import org.apache.commons.httpclient.methods.FileRequestEntity;
import org.apache.commons.httpclient.methods.GetMethod;
import org.apache.commons.httpclient.methods.PutMethod;
import org.apache.commons.httpclient.params.HttpMethodParams;

/**
 * This is a simple http client that will be used for testing phpdfs.
 *
 * the algorithm is as follows:
 *
 * start the client
 * the client will alternate between uploads and downloads
 *  randomly select from the list of possible files to upload
 *  generate a unique name using the UUID class
 *  upload the file
 *  put the name of the file in an index (hashmap)
 *  upon successful upload
 *      randomly choose a file from those that we have uploaded
 *      download the file,
 *      note the statistics of the download (we will not be saving the file)
 *          size, name, time to download, successful or not
 * 
 *
 *
 * @author shane
 */
public class PHPDFSTestClient extends Thread {

    private long time = 0;
    private long bytesToUpload = 0;
    private double writeLoad = 0;
    private long sleep = 0;
    static AtomicInteger numThreads = new AtomicInteger(0);

    private long myTotalBytesUp = 0;
    private long myTotalBytesDown = 0;
    private long myTotalBytes = 0;
    
    private long myTotalRequestsUp = 0;
    private long myTotalRequestsDown = 0;
    private long myTotalRequests = 0;

    private long myTotalFailuresUp = 0;
    private long myTotalFailuresDown = 0;
    private long myTotalFailures = 0;

    private long myTotalTimeUp = 0;
    private long myTotalTimeDown = 0;
    private long myTotalTime = 0;

    private static AtomicLong totalBytesUp = new AtomicLong(0);
    private static AtomicLong totalBytesDown = new AtomicLong(0);
    private static AtomicLong totalBytes = new AtomicLong(0);

    private static AtomicLong totalRequestsUp = new AtomicLong(0);
    private static AtomicLong totalRequestsDown = new AtomicLong(0);
    private static AtomicLong totalRequests = new AtomicLong(0);

    private static AtomicLong totalFailuresUp = new AtomicLong(0);
    private static AtomicLong totalFailuresDown = new AtomicLong(0);
    private static AtomicLong totalFailures = new AtomicLong(0);

    private static AtomicLong totalTimeUp = new AtomicLong(0);
    private static AtomicLong totalTimeDown = new AtomicLong(0);
    private static AtomicLong totalTime = new AtomicLong(0);

    private static AtomicLong startTime = new AtomicLong(0);
    private static AtomicLong wallClock = new AtomicLong(0);

    private Random ran = new Random();
    private HttpClient client = new HttpClient();
    private ArrayList<String> uploadedFiles = new ArrayList<String>();
    private ArrayList<String> downloadedFiles = new ArrayList<String>();
    private ArrayList<String> filesToUpload = null;
    private ArrayList<String> baseUrls = null;

    public PHPDFSTestClient( long bytesToUpload, int numThreads, double writeLoad, String filesToUploadFile, String baseUrlFile, long sleep ){
        this.bytesToUpload = bytesToUpload;
        this.writeLoad = writeLoad;
        PHPDFSTestClient.numThreads.compareAndSet(0, numThreads);
        filesToUpload = fileToArrayList(filesToUploadFile);
        baseUrls = fileToArrayList(baseUrlFile);
        this.sleep = sleep;
    }

    /**
     * opens the passed file and puts each line into an array
     * @param filename
     * @return
     */
    private static ArrayList<String> fileToArrayList( String filename ){
        ArrayList<String> lines = new ArrayList<String>();
        try{
            BufferedReader br = new BufferedReader( 
                    new InputStreamReader(
                        new FileInputStream( filename )
                    )
            );
            
            String line = null;
            while( (line = br.readLine() ) != null ){
                line = line.trim();
                if( !line.startsWith("//") && line.length() > 0 ){
                    lines.add(line);
                }
            }
            
        } catch( FileNotFoundException e ){
            System.err.println("could not open file " + filename + " for reading! " );
             e.printStackTrace();
            System.exit(99);
        } catch( IOException e ){
            System.err.println("IOException when processing " + filename );
             e.printStackTrace();
            System.exit(99);
        }
        return lines;
    }
    
    /**
     * the client will alternate between uploads and downloads
     *  randomly select from the list of possible files to upload
     *  generate a unique name using the UUID class
     *  upload the file
     *  put the name of the file in an index (hashmap)
     *  upon successful upload
     *      randomly choose a file from those that we have uploaded from the main central hashmap
     *      download the file,
     *      note the statistics of the download (we will not be saving the file)
     *          size, name, time to download, successful or not in the main central hashmap
     *
     * there will also be a hashmap that holds all erros that are encounteerd during the test
     * in the hash map we will put the name of the file that failed, which client and server experienced the failure
     * and why it failed
     *
     * @param url
     * @param filename
     * @param type
     *
     */

    private void doPut(String url, String filename, String type ){

        // set the method for the client
        PutMethod method = new PutMethod(url);
        File file = new File(filename);
        FileRequestEntity fre = new FileRequestEntity(file, type);
        method.setRequestEntity(fre);

        // Provide custom retry handler is necessary
        method.getParams().setParameter(HttpMethodParams.RETRY_HANDLER,
            new DefaultHttpMethodRetryHandler(1, false));

        try {
            // Execute the method.
            time = System.currentTimeMillis();
            int statusCode = client.executeMethod(method);
            time = System.currentTimeMillis() - time;
            myTotalTimeUp += time;
            myTotalTime += time;

            if (statusCode != HttpStatus.SC_OK && statusCode != HttpStatus.SC_NO_CONTENT ) {
                myTotalFailuresUp++;
                myTotalFailures++;
                System.err.println("Method failed while PUTting " + url + " for thread " + getName() + ": " + method.getStatusLine() );
            } else {
                uploadedFiles.add(url);

                myTotalBytesUp += file.length();
                myTotalRequestsUp++;

                myTotalBytes += file.length();
                myTotalRequests++;
            }

        } catch (HttpException e) {
            System.err.println("Fatal protocol violation while PUTting " + url + " for thread " + getName() + ": " + e.getMessage());
            e.printStackTrace();
        } catch (IOException e) {
            System.err.println("Fatal transport error while PUTting " + url + " for thread  " + getName() + ": " + e.getMessage());
            e.printStackTrace();
        } finally {
            // Release the connection.
            method.releaseConnection();
        }
    }
    
    private void doGet(String url){
        // set the method for the client
        GetMethod method = new GetMethod(url);

        // Provide custom retry handler is necessary
        method.getParams().setParameter(HttpMethodParams.RETRY_HANDLER,
            new DefaultHttpMethodRetryHandler(1, false));

        try {
            // Execute the method.
            time = System.currentTimeMillis();
            int statusCode = client.executeMethod(method);
            time = System.currentTimeMillis() - time;
            myTotalTimeDown += time;
            myTotalTime += time;

            if (statusCode != HttpStatus.SC_OK) {
                myTotalFailuresDown++;
                System.err.println("Method failed while GETting " + url + " for thread " + getName() + ": " + method.getStatusLine() );
            } else {
                // Read the response body and discard it
                InputStream responseStream = method.getResponseBodyAsStream();
                while( responseStream.read( ) != -1 ){ responseStream.skip( 30720000 ); }
                downloadedFiles.add(url);

                myTotalBytesDown += method.getResponseContentLength();
                myTotalRequestsDown++;

                myTotalBytes += method.getResponseContentLength();
                myTotalRequests++;
            }

        } catch (HttpException e) {
            System.err.println("Fatal protocol violation while GETting " + url + " for thread " + getName() + ": " + e.getMessage());
            e.printStackTrace();
        } catch (IOException e) {
            System.err.println("Fatal transport error while GETting " + url + " for thread  " + getName() + ": " + e.getMessage());
            e.printStackTrace();
        } finally {
            // Release the connection.
            method.releaseConnection();
        }
    }

    public void doTest( ){
        runTest();
        writeStats();
    }

    /**
     * we do the upload and download iterations until we have input
     * more than the number of bytes passed
     */
    private void runTest(){
        startTime.compareAndSet(0, System.currentTimeMillis() );
        while( myTotalBytesUp < bytesToUpload ){

            // solve randomly according to the getsPerUpload value
            String whichUrl;
            
            if( ( ran.nextDouble() ) < writeLoad ){
                // randomly select a baseurl and filename to use for an upload
                whichUrl = baseUrls.get( ran.nextInt( baseUrls.size() ) ) + "/" + UUID.randomUUID().toString();

                // randomly choose a file for upload
                String whichFile = filesToUpload.get( ran.nextInt( filesToUpload.size() ) );
                String fileType = "text/plain";

                doPut( whichUrl, whichFile, fileType );
            } else if( uploadedFiles.size() > 0 ){
                whichUrl = uploadedFiles.get( ran.nextInt( uploadedFiles.size() ) );
                doGet(whichUrl);
            }

            try{
                int sleepTime = sleep > 0 ? ran.nextInt( (int) sleep ) : 0;
                if( sleepTime > 0 ){
                    sleep( sleepTime );
                }
            } catch( InterruptedException e){
                
            }
        }
    }

    private void writeStats(){
        totalBytesUp.addAndGet(myTotalBytesUp);
        totalBytesDown.addAndGet(myTotalBytesDown);
        totalBytes.addAndGet(myTotalBytes);

        totalRequestsUp.addAndGet(myTotalRequestsUp);
        totalRequestsDown.addAndGet(myTotalRequestsDown);
        totalRequests.addAndGet(myTotalRequests);

        totalFailuresUp.addAndGet(myTotalFailuresUp);
        totalFailuresDown.addAndGet(myTotalFailuresDown);
        totalFailures.addAndGet(myTotalFailures);

        totalTimeUp.addAndGet(myTotalTimeUp);
        totalTimeDown.addAndGet(myTotalTimeDown);
        totalTime.addAndGet(myTotalTime);
        long end = System.currentTimeMillis();
        //endTime.getAndSet( end );
        wallClock.set(end - startTime.get());

        /**
        System.out.println( "Report for thread " + getName() );
        System.out.println( "Requests: " );
        System.out.println( "  up: " + myTotalRequestsUp );
        System.out.println( "  down: " + myTotalRequestsDown );
        System.out.println( "  total: " + myTotalRequests );
        System.out.println( "" );

        System.out.println( "Bytes: " );
        System.out.println( "  up: " + myTotalBytesUp );
        System.out.println( "  down: " + myTotalBytesDown );
        System.out.println( "  total: " + myTotalBytes );
        System.out.println( "" );

        System.out.println( "Failures: " );
        System.out.println( "  up: " + myTotalFailuresUp );
        System.out.println( "  down: " + myTotalFailuresDown );
        System.out.println( "  total: " + myTotalFailures );
        System.out.println( "" );

        System.out.println( "Time: " );
        System.out.println( "  up: " + myTotalTimeUp );
        System.out.println( "  down: " + myTotalTimeDown );
        System.out.println( "  total: " + myTotalTime );
        System.out.println( "" );

        System.out.println( "Requests / Sec: " );
        System.out.println( "  up: " + ( (float)myTotalRequestsUp / ((float)myTotalTimeUp / (float)1000 )) );
        System.out.println( "  down: " + ( (float)myTotalRequestsDown / ((float)myTotalTimeDown / (float)1000 )) );
        System.out.println( "  avg: " + ( (float)myTotalRequests / ((float)myTotalTime / (float)1000 )) );
        System.out.println( "" );

        System.out.println( "Throughput - Bytes / Sec: " );
        System.out.println( "  up: " + ( (float)myTotalBytesUp / ((float)myTotalTimeUp / (float)1000 )) );
        System.out.println( "  down: " + ( (float)myTotalBytesDown / ((float)myTotalTimeDown / (float)1000 )) );
        System.out.println( "  total: " + ( (float)myTotalBytes / ((float)myTotalTime / (float)1000 )) );
        System.out.println( "" );
        System.out.println( "_____________________________" );
        System.out.println( "" );
         */
        System.out.println("completed " + totalRequests.get() );
        
        if( numThreads.decrementAndGet() == 0 ){

            String report =
             "========= Overall Report =========\n" +
             "Requests: \n" +
             "  up: " + totalRequestsUp.get() + "\n" +
             "  down: " + totalRequestsDown.get() + "\n" +
             "  total: " + totalRequests.get() + "\n" +
             "\n" +

             "Bytes: \n" +
             "  up: " + totalBytesUp.get() + "\n" +
             "  down: " + totalBytesDown.get() + "\n" +
             "  total: " + totalBytes.get() + "\n" +
             "\n" +

             "Failures: \n" +
             "  up: " + totalFailuresUp.get() + "\n" +
             "  down: " + totalFailuresDown.get() + "\n" +
             "  total: " + totalFailures.get() + "\n" +
             "\n" +

             "Time: \n" +
             "  up: " + totalTimeUp.get() + "\n" +
             "  down: " + totalTimeDown.get() + "\n" +
             "  total: " + totalTime.get() + "\n" +
             "  wall clock: " + wallClock.get() + "\n" +
             "\n" +

             "Requests / Sec: \n" +
             "  mean up: " + ( (float) totalRequestsUp.get() / ((float)totalTimeUp.get() / (float)1000 ) ) + "\n" +
             "  mean down: " + ( (float)totalRequestsDown.get() / ( (float)totalTimeDown.get() / (float)1000 ) ) + "\n" +
             "  mean total: " + ( (float)totalRequests.get() / ((float)totalTime.get() / (float)1000 )) + "\n" +
             "  mean total (across all requests): " + ( (float)totalRequests.get() / ((float)wallClock.get() / (float)1000 ) ) + "\n" +
             "\n" +

             "Throughput - Bytes / Sec: \n" +
             "  mean up: " + ( (float) totalBytesUp.get() / ((float)totalTimeUp.get() / (float)1000 ) ) + "\n" +
             "  mean down: " + ( (float)totalBytesDown.get() / ( (float)totalTimeDown.get() / (float)1000 ) ) + "\n" +
             "  mean total: " + ( (float)totalBytes.get() / ( (float)totalTime.get() / (float)1000 ) ) + "\n" +
             "  mean total (across all requests): " + ( (float)totalBytes.get() / ( (float)wallClock.get() / (float)1000 ) ) + "\n" +
             "\n" +
             "_____________________________\n" +
             "\n";
            System.out.println( report );
            try{
                File statsFile = new File("/tmp/phpdfs.stats.txt");
                FileWriter stats = new FileWriter(statsFile);
                stats.write(report);
                stats.flush();
            } catch( IOException e ) {
                e.printStackTrace();
                System.exit(99);
            }
        }
    }

    @Override
    public void run() {
        doTest();
    }

    public static void main( String args[] ){
        double writeLoad = 0.2;
        long sleep = 0;
        int threads = 1;
        long bytesToUploadPerThread = 2048000L;
        String filesToUpload = "/Users/shane/dev/phpdfs/utils/filesToUpload";
        String baseUrls = "/Users/shane/dev/phpdfs/utils/baseUrls";

        if( args.length == 6 ){
            threads = Integer.parseInt( args[0] );
            bytesToUploadPerThread = Long.parseLong( args[1] );
            writeLoad = Double.parseDouble( args[2] );
            filesToUpload = args[3];
            baseUrls = args[4];
            sleep = Long.parseLong( args[5] );
        }
        
        for( int n = 0; n < threads; n++ ){
            PHPDFSTestClient thw = new PHPDFSTestClient( bytesToUploadPerThread, threads, writeLoad, filesToUpload, baseUrls, sleep );
            thw.start();
        }
    }
}

