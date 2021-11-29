package org.adriangreen.misc;

import org.apache.commons.codec.language.DoubleMetaphone;
import org.apache.commons.lang3.StringEscapeUtils;
import org.apache.commons.cli.*;

import java.io.*;
import java.nio.file.Paths;


/**
 * Creates reference test-data for Double-Metaphone algorithm.
 * credits to https://github.com/hgoebl/doublemetaphone
 */
public class CreateTestDataFromWords {
    public static void main(String[] argv) throws FileNotFoundException {
        Options options = new Options();
        Option osizes = new Option("s", "sizes", true, "Command separated sizes");
        Option osource = new Option("i", "source", true, "Source file");
        Option obasename = new Option("b", "basename", true, "Base output file name");
        Option oext = new Option("e", "ext", true, "Output file extension");

        osizes.setRequired(true);
        osource.setRequired(true);
        obasename.setRequired(true);

        options.addOption(osizes);
        options.addOption(osource);
        options.addOption(obasename);
        options.addOption(oext);

        CommandLineParser parser = new DefaultParser();
        HelpFormatter formatter = new HelpFormatter();

        String ext = ".php";

        CommandLine cmd = null;
        try {
            // parse the command line arguments
            cmd = parser.parse(options, argv);
            if(cmd.hasOption("e")) {
                ext = cmd.getOptionValue("e");
            }
        } catch (ParseException exp) {
            // oops, something went wrong
            System.err.println("Parsing failed.  Reason: " + exp.getMessage());
            System.exit(1);
        }

        try {
            String sizes = cmd.getOptionValue('s');
            String source = cmd.getOptionValue('i');
            String basename = cmd.getOptionValue('b');
            source = Paths.get(source).toAbsolutePath().toString();
            basename = Paths.get(basename).toAbsolutePath().toString();
            for(String strsize : sizes.split(",")) {
                int size = Integer.parseInt(strsize.trim());
                BufferedReader reader = new BufferedReader(new InputStreamReader(new FileInputStream(source), "UTF-8"));
                BufferedWriter writer = new BufferedWriter(new OutputStreamWriter(new FileOutputStream(basename + "-" + strsize + ext), "UTF-8"));

                DoubleMetaphone doubleMetaphone = new DoubleMetaphone();
                doubleMetaphone.setMaxCodeLen(size);

                writer.append("<?php");
                writer.newLine();
                writer.append("/* [word, primary_word, alternate_word] */");
                writer.newLine();
                writer.append("return [");
                writer.newLine();

                StringBuilder sbuf = new StringBuilder();
                String word;

                while (null != (word = reader.readLine())) {
                    sbuf.setLength(0);
                    sbuf.append('[')
                            .append(encodePHPString(word)).append(',')
                            .append(encodePHPString(doubleMetaphone.doubleMetaphone(word, false))).append(',')
                            .append(encodePHPString(doubleMetaphone.doubleMetaphone(word, true))).append(',')
                            .append(']').append(',');
                    writer.append(sbuf);
                    writer.newLine();
                }
                writer.append("];");
                writer.newLine();

                reader.close();
                writer.close();
            }

            System.exit(0);
        } catch (Exception e) {
            e.printStackTrace();
            System.exit(-1);
        }
    }

    private static String encodePHPString(String value) {
        return "\"" + StringEscapeUtils.escapeJson(value) + "\"";
    }
}
