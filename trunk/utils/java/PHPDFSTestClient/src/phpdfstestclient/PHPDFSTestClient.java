package phpdfstestclient;

import java.io.File;
import java.io.IOException;
import java.io.InputStream;
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
    private int getsPerUpload = 0;
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
    private static AtomicLong endTime = new AtomicLong(0);
    private static AtomicLong wallClock = new AtomicLong(0);

    private Random ran = new Random();
    private HttpClient client = new HttpClient();
    private ArrayList<String> uploadedFiles = new ArrayList<String>();
    private ArrayList<String> downloadedFiles = new ArrayList<String>();
    private String[] filesToUpload = new String[]{
        "/tmp/40k",
        "/tmp/50k",
        //"/tmp/100k",
        //"/tmp/500k",
        //"/tmp/1m",
        //"/tmp/3m",
        //"/tmp/5m",
        //"/tmp/10m",
        //"/tmp/15m",
        //"/tmp/20m",
    };

    private String[] baseUrls = new String[]{
        "http://192.168.0.11/dfs.php",
        //"http://192.168.0.2:81/dfs81.php",
        //"http://192.168.0.2:82/dfs82.php",
        //"http://192.168.0.2:83/dfs83.php",
        //"http://192.168.0.2:84/dfs84.php",
        //"http://192.168.0.2:85/dfs85.php",
    };

    public PHPDFSTestClient( long bytesToUpload, int numThreads, int getsPerUpload ){
        this.bytesToUpload = bytesToUpload;
        this.getsPerUpload = getsPerUpload;
        PHPDFSTestClient.numThreads.compareAndSet(0, numThreads);
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

    /**
     * we do the upload and download iterations until we have input
     * more than the number of bytes passed
     */
    public void doTest( ){
        runTest();
        writeStats();
    }

    private void runTest(){
        startTime.compareAndSet(0, System.currentTimeMillis() );
        while( myTotalBytesUp < bytesToUpload ){
            // randomly select a baseurl and filename to use for an upload
            String whichUrl = baseUrls[ran.nextInt(baseUrls.length)] + "/" + UUID.randomUUID().toString();
            // randomly choose a file for upload
            String whichFile = filesToUpload[ran.nextInt(filesToUpload.length)];
            String fileType = "text/plain";

            doPut( whichUrl, whichFile, fileType );

            for( int i = 0; i < getsPerUpload; i++ ){
                whichUrl = uploadedFiles.get( ran.nextInt( uploadedFiles.size() ) );
                doGet(whichUrl);
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

        long end = endTime.getAndSet(System.currentTimeMillis());
        wallClock.set(end - startTime.get());

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

        if( numThreads.decrementAndGet() == 0 ){
            System.out.println( "========= Overall Report =========" );
            System.out.println( "Requests: " );
            System.out.println( "  up: " + totalRequestsUp.get() );
            System.out.println( "  down: " + totalRequestsDown.get() );
            System.out.println( "  total: " + totalRequests.get() );
            System.out.println( "" );

            System.out.println( "Bytes: " );
            System.out.println( "  up: " + totalBytesUp.get() );
            System.out.println( "  down: " + totalBytesDown.get() );
            System.out.println( "  total: " + totalBytes.get() );
            System.out.println( "" );

            System.out.println( "Failures: " );
            System.out.println( "  up: " + totalFailuresUp.get() );
            System.out.println( "  down: " + totalFailuresDown.get() );
            System.out.println( "  total: " + totalFailures.get() );
            System.out.println( "" );

            System.out.println( "Time: " );
            System.out.println( "  up: " + totalTimeUp.get() );
            System.out.println( "  down: " + totalTimeDown.get() );
            System.out.println( "  total: " + (totalTimeUp.get() + totalTimeDown.get() ) );
            System.out.println( "  wall clock: " + wallClock.get() );
            System.out.println( "" );

            System.out.println( "Requests / Sec: " );
            System.out.println( "  mean up: " + ( (float) totalRequestsUp.get() / ((float)totalTimeUp.get() / (float)1000 ) ) );
            System.out.println( "  mean down: " + ( (float)totalRequestsDown.get() / ( (float)totalTimeDown.get() / (float)1000 ) ) ) ;
            System.out.println( "  mean total: " + ( (float)totalRequests.get() / ((float)totalTime.get() / (float)1000 )) );
            System.out.println( "  mean total (across all requests): " + ( (float)totalRequests.get() / ((float)wallClock.get() / (float)1000 )) );
            System.out.println( "" );

            System.out.println( "Throughput - Bytes / Sec: " );
            System.out.println( "  mean up: " + ( (float) totalBytesUp.get() / ((float)totalTimeUp.get() / (float)1000 ) ) );
            System.out.println( "  mean down: " + ( (float)totalBytesDown.get() / ( (float)totalTimeDown.get() / (float)1000 ) ) ) ;
            System.out.println( "  mean total: " + ( (float)totalBytes.get() / ( (float)totalTime.get() / (float)1000 ) ) ) ;
            System.out.println( "  mean total (across all requests): " + ( (float)totalBytes.get() / ( (float)wallClock.get() / (float)1000 ) ) ) ;
            System.out.println( "" );
            System.out.println( "_____________________________" );
            System.out.println( "" );
        }
    }

    @Override
    public void run() {
        doTest();
    }

    public static void main( String args[] ){
        int getsPerUpload = 1;
        int threads = 50;
        long bytesToUploadPerThread = 2048000L;
        if( args.length == 3 ){
            threads = Integer.parseInt( args[0] );
            bytesToUploadPerThread = Long.parseLong( args[1] );
            getsPerUpload = Integer.parseInt( args[2] );
        }
        
        for( int n = 0; n < threads; n++ ){
            PHPDFSTestClient thw = new PHPDFSTestClient( bytesToUploadPerThread, threads, getsPerUpload );
            thw.start();
        }
    }
}

